<?php
namespace App\Http\Controllers;

use App\Models\Conversation;
use App\Models\Message;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;
use App\Services\WebScraperService;

class GeminiController extends Controller
{
    protected $webScraperService;

    public function __construct(WebScraperService $webScraperService)
    {
        $this->webScraperService = $webScraperService;
    }

    public function show($conversationId = null)
    {
        $userId = Auth::id();
        $conversations = Conversation::where('user_id', $userId)
                                     ->withCount('messages')
                                     ->orderBy('updated_at', 'desc')
                                     ->get();

        $messages = collect(); // Always initialize as an empty collection for JS-only rendering

        if ($conversationId) {
            $conversation = Conversation::where('id', $conversationId)
                                        ->where('user_id', $userId)
                                        ->first();
            if (!$conversation) {
                // If conversation not found, redirect to base assistant page with a message
                return redirect()->route('assistant.show')->with('error', 'Conversation not found or unauthorized.');
            }
        }
        // Removed the logic that automatically sets conversationId to the first one
        // if no specific conversation ID is provided.

        return view('assistant', [
            'conversationId' => $conversationId,
            'conversations' => $conversations,
            'messages' => $messages,
            'professorAvatarUrl' => asset('images/professor_avatar.jpg'),
        ]);
    }

    public function ask(Request $request, $conversationId = null)
    {
        try {
            $request->validate([
                'prompt' => 'nullable|string',
                'fileIds' => 'nullable|array',
                'fileIds.*' => 'string',
                'api_key' => 'required|string',
                'conversation_id' => 'nullable|string',
            ]);

            $apiKey = $request->input('api_key');
            $conversationId = $request->input('conversation_id', $conversationId);

            $originalUserPrompt = $request->input('prompt', '');
            $fileIds = $request->input('fileIds', []);
            $parts = [];
            $processedFilePaths = [];

            // Check for URLs in the prompt and scrape content
            $scrapedContent = '';
            preg_match_all('/https?:\/\/\S+/', $originalUserPrompt, $matches); // Use originalUserPrompt for URL detection
            foreach ($matches[0] as $url) {
                $scrapedContent .= "Content from " . $url . ":\n" . $this->webScraperService->scrape($url) . "\n\n";
            }

            $promptForGemini = $originalUserPrompt; // Initialize with original prompt
            if (!empty($scrapedContent)) {
                $promptForGemini = "Scraped content:\n" . $scrapedContent . "\n\n" . $originalUserPrompt;
            }

            // Retrieve or create conversation
            $userId = Auth::id();
            if (!$userId) {
                Log::warning('ask method: Auth::id() is null, using placeholder UUID.');
                $userId = 'a0eebc99-9c0b-4ef8-bb6d-6bb9bd380a11'; // Placeholder UUID
            }
            
            $conversation = null;
            if ($conversationId) {
                $conversation = Conversation::where('id', $conversationId)
                                            ->where('user_id', $userId)
                                            ->first();
            }

            if (!$conversation && (!empty($originalUserPrompt) || !empty($fileIds))) {
                $conversation = Conversation::create([
                    'user_id' => $userId,
                    'title' => \Illuminate\Support\Str::limit($originalUserPrompt, 50) ?: 'New Chat'
                ]);
            } else if ($conversation && $conversation->title === 'New Chat' && !empty($originalUserPrompt)) {
                $conversation->title = \Illuminate\Support\Str::limit($originalUserPrompt, 50);
                $conversation->save();
            }
            // If conversation title is not 'New Chat' and prompt is not empty, update title
            else if ($conversation && $conversation->title !== 'New Chat' && !empty($originalUserPrompt)) {
                $conversation->title = \Illuminate\Support\Str::limit($originalUserPrompt, 50);
                $conversation->save();
            }

            // Fetch existing messages for the history
            $historyMessages = Message::where('conversation_id', $conversation->id)
                ->orderBy('created_at', 'asc')
                ->get();

            $contents = [];

            foreach ($historyMessages as $message) {
                $role = $message->role === 'user' ? 'user' : 'model';
                // For now, we'll assume history messages are text-only.
                // A more robust solution would handle historical file attachments.
                $contents[] = [
                    'role' => $role,
                    'parts' => [['text' => $message->content]]
                ];
            }

            // Check for duplicate user message within a short timeframe
            $existingUserMessage = Message::where('conversation_id', $conversation->id)
                ->where('role', 'user')
                ->where('content', $originalUserPrompt ?? '')
                ->whereRaw('file_ids::text = ?', [json_encode($fileIds)]) // Correct JSON comparison for PostgreSQL
                ->where('created_at', '>=', now()->subSeconds(5)) // Within the last 5 seconds
                ->first();

            if ($existingUserMessage) {
                Log::info('Duplicate user message detected, skipping save.');
                // If a duplicate is found, we can skip the rest of the logic and return
                // the current conversation messages, as the frontend will re-fetch anyway.
                // This prevents duplicate API calls and message saves.
                return response()->json([
                    'output' => 'Duplicate message received, processing skipped.',
                    'conversation_id' => $conversation->id
                ]);
            }

            // Save user message
            Message::create([
                'conversation_id' => $conversation->id,
                'role' => 'user',
                'content' => $originalUserPrompt ?? '', // Save original prompt to database
                'file_ids' => $fileIds,
            ]);

            $currentMessageParts = [];
            if (!empty($promptForGemini)) { // Use promptForGemini for API request
                $currentMessageParts[] = ['text' => $promptForGemini];
            }

            foreach ($fileIds as $fileId) {
                $filePath = 'tmp/' . $fileId;
                if (Storage::exists($filePath)) {
                    $currentMessageParts[] = [
                        'inline_data' => [
                            'mime_type' => Storage::mimeType($filePath),
                            'data' => base64_encode(Storage::get($filePath))
                        ]
                    ];
                    $processedFilePaths[] = $filePath;
                } else {
                    Log::warning('Temporary file not found, skipping.', ['fileId' => $fileId]);
                }
            }
            
            if (empty($currentMessageParts)) {
                return response()->json(['error' => 'Prompt or valid file(s) are required.'], 400);
            }

            // Add the current user message to the API request contents
            $contents[] = [
                'role' => 'user',
                'parts' => $currentMessageParts
            ];

            $system_prompt = File::get(resource_path('prompts/capstone_mentor_prompt.txt'));

            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
                'x-goog-api-key' => $apiKey,
            ])->retry(3, 1000, function ($exception) {
                return ($exception instanceof \Illuminate\Http\Client\RequestException && $exception->response && $exception->response->status() === 503) || $exception instanceof \Illuminate\Http\Client\ConnectionException;
            }, false)->post("https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash-lite:generateContent", [
                'contents' => $contents,
                'systemInstruction' => [
                    'role' => 'system',
                    'parts' => [['text' => $system_prompt]]
                ]
            ]);

            $response->throw();

            $responseData = $response->json();
            $assistantResponseText = 'Sorry, something went wrong.';

            if (isset($responseData['candidates'][0]['content']['parts'][0]['text'])) {
                $assistantResponseText = $responseData['candidates'][0]['content']['parts'][0]['text'];
            } else {
                Log::warning('Gemini API response did not contain expected text.', ['response' => $responseData]);
            }

            // Save assistant message
            Message::create([
                'conversation_id' => $conversation->id,
                'role' => 'assistant',
                'content' => $assistantResponseText,
                'file_ids' => [], // Assistant responses don't have files
            ]);
            
            // Update conversation's updated_at timestamp
            $conversation->touch();

            return response()->json([
                'output' => $assistantResponseText,
                'conversation_id' => $conversation->id
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::error('Validation error in ask method: ' . $e->getMessage(), ['errors' => $e->errors()]);
            return response()->json(['error' => $e->errors()], 422);

        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            Log::error('Gemini API Connection Exception: ' . $e->getMessage());
            return response()->json(['error' => 'A connection error occurred. Please check your internet connection and try again.'], 500);
        } catch (RequestException $e) {
            Log::error('Gemini API Request Exception: ' . $e->getMessage(), ['response_body' => $e->response ? $e->response->body() : 'No response body']);
            
            $statusCode = $e->response ? $e->response->status() : 500;
            $errorMessage = 'An API error occurred.';

            if ($statusCode === 429) {
                $errorMessage = 'I apologize, but I have exceeded my current query quota. Please try again after some time, or check your API plan.';
            } else {
                $errorMessage = 'API Error: ' . $e->getMessage();
            }

            return response()->json(['error' => $errorMessage], $statusCode);
        } catch (\Exception $e) {
            Log::error('An unexpected error occurred: ' . $e->getMessage());
            return response()->json(['error' => 'An unexpected server error occurred: ' . $e->getMessage()], 500);
        } finally {
            if (!empty($processedFilePaths)) {
                Storage::delete($processedFilePaths);
            }
        }
    }
    public function validateApiKey(Request $request)
    {
        $request->validate([
            'api_key' => 'required|string',
        ]);

        $apiKey = $request->input('api_key');

        try {
            // Attempt to list models to validate the API key
            $response = Http::withHeaders([
                'x-goog-api-key' => $apiKey,
            ])->get("https://generativelanguage.googleapis.com/v1beta/models");

            if ($response->successful()) {
                return response()->json(['message' => 'API Key is valid.']);
            } else {
                Log::error('API Key validation failed: ' . $response->body());
                return response()->json(['message' => 'Invalid API Key. Please check your key and try again.'], 400);
            }
        } catch (\Exception $e) {
            Log::error('Error during API key validation: ' . $e->getMessage());
            return response()->json(['message' => 'An error occurred while validating the API key.'], 500);
        }
    }

    public function deleteConversation($conversationId)
    {
        $userId = Auth::id();
        Log::info("Attempting to delete conversation {$conversationId} for user {$userId}");

        $conversation = Conversation::where('id', $conversationId)
                                    ->where('user_id', $userId)
                                    ->first();

        if (!$conversation) {
            Log::warning("Conversation {$conversationId} not found or unauthorized for user {$userId}");
            return redirect()->back()->with('error', 'Conversation not found or unauthorized.');
        }

        $conversation->delete(); // Messages will be cascade deleted
        Log::info("Conversation {$conversationId} deleted successfully for user {$userId}");

        // Redirect to the base assistant page or the first available conversation
        $firstConversation = Conversation::where('user_id', $userId)->orderBy('updated_at', 'desc')->first();
        if ($firstConversation) {
            return redirect()->route('assistant.show', ['conversationId' => $firstConversation->id])
                            ->with('success', 'Conversation deleted successfully!');
        } else {
            return redirect()->route('assistant.show')
                            ->with('success', 'Conversation deleted successfully. No other conversations.');
        }
    }

    public function newConversation()
    {
        $userId = Auth::id();
        $conversations = Conversation::where('user_id', $userId)
                                     ->withCount('messages')
                                     ->orderBy('updated_at', 'desc')
                                     ->get();

        return view('assistant', [
            'conversationId' => null, // Explicitly pass null for new conversations
            'conversations' => $conversations,
            'messages' => collect(), // Empty collection for new chat
            'professorAvatarUrl' => asset('images/professor_avatar.jpg'),
        ]);
    }

    public function messages($conversationId)
    {
        $userId = Auth::id();

        $conversation = Conversation::where('id', $conversationId)
                                    ->where('user_id', $userId)
                                    ->firstOrFail();

        $messages = Message::where('conversation_id', $conversation->id)
                           ->orderBy('created_at', 'asc')
                           ->get();

        return response()->json(['messages' => $messages]);
    }
}

