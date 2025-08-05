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

    public function ask(Request $request)
    {
        try {
            $request->validate([
                'prompt' => 'nullable|string',
                'fileIds' => 'nullable|array',
                'fileIds.*' => 'string',
                'conversation_id' => 'nullable|uuid', // Add conversation_id validation
            ]);

            $apiKey = env('GEMINI_API_KEY');
            if (!$apiKey) {
                return response()->json(['error' => 'GEMINI_API_KEY is not set.'], 500);
            }

            $originalUserPrompt = $request->input('prompt', ''); // Store the original prompt
            $fileIds = $request->input('fileIds', []);
            $conversationId = $request->input('conversation_id');
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
            $conversation = null;
            $userId = Auth::id() ?? 'a0eebc99-9c0b-4ef8-bb6d-6bb9bd380a11'; // Placeholder UUID
            if ($conversationId) {
                $conversation = Conversation::where('id', $conversationId)->where('user_id', $userId)->first();
            }

            if (!$conversation) {
                $conversation = Conversation::create([
                    'user_id' => $userId,
                    'title' => \Illuminate\Support\Str::limit($originalUserPrompt, 50) ?: 'New Chat', // Use original for title
                ]);
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
                'conversation_id' => $conversation->id, // Return conversation ID
            ]);

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
    public function newConversation(Request $request)
    {
        // For now, assuming a default user_id or authenticated user
        // In a real application, ensure user is authenticated
        $conversation = Conversation::create([
            'user_id' => Auth::id() ?? 'a0eebc99-9c0b-4ef8-bb6d-6bb9bd380a11', // Placeholder UUID if Auth::id() is null
            'title' => $request->input('title', 'New Chat'),
        ]);

        return response()->json(['conversation_id' => $conversation->id]);
    }

    public function getConversations()
    {
        // For now, assuming a default user_id or authenticated user
        $userId = Auth::id() ?? 'a0eebc99-9c0b-4ef8-bb6d-6bb9bd380a11';
        $conversations = Conversation::where('user_id', $userId)
                                     ->withCount('messages') // Add message count
                                     ->orderBy('updated_at', 'desc')
                                     ->get();
        return response()->json(['conversations' => $conversations]);
    }

    public function getMessages(Request $request, $conversationId)
    {
        $messages = Message::where('conversation_id', $conversationId)
                           ->orderBy('created_at', 'asc')
                           ->get();
        return response()->json(['messages' => $messages]);
    }

    public function deleteConversation($conversationId)
    {
        $conversation = Conversation::find($conversationId);

        if (!$conversation) {
            return response()->json(['message' => 'Conversation not found'], 404);
        }

        // Optional: Add authorization check here if needed (e.g., only owner can delete)
        // if ($conversation->user_id !== Auth::id()) {
        //     return response()->json(['message' => 'Unauthorized'], 403);
        // }

        $conversation->delete(); // Messages will be cascade deleted

        return response()->json(['message' => 'Conversation deleted successfully']);
    }
    public function updateConversationTitle(Request $request)
    {
        $request->validate([
            'conversation_id' => 'required|uuid',
            'user_prompt' => 'required|string',
        ]);

        $conversationId = $request->input('conversation_id');
        $userPrompt = $request->input('user_prompt'); // This is the original user prompt for title generation

        $conversation = Conversation::find($conversationId);

        if (!$conversation) {
            return response()->json(['error' => 'Conversation not found.'], 404);
        }

        $apiKey = env('GEMINI_API_KEY');
        if (!$apiKey) {
            return response()->json(['error' => 'GEMINI_API_KEY is not set.'], 500);
        }

        try {
            // Use Gemini to generate a title based on the user's prompt
            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
                'x-goog-api-key' => $apiKey,
            ])->post("https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash-lite:generateContent", [
                'contents' => [
                    ['parts' => [['text' => "You are Professor Johnson. Generate a concise, academic-style title (max 10 words) for a chat conversation based on this initial user message: \"{$userPrompt}\". Do not include any markdown formatting in the title."]]]
                ]
            ]);

            $response->throw();
            $responseData = $response->json();
            $generatedTitle = 'New Chat'; // Default if AI fails

            if (isset($responseData['candidates'][0]['content']['parts'][0]['text'])) {
                $generatedTitle = $responseData['candidates'][0]['content']['parts'][0]['text'];
                // Remove markdown formatting (e.g., **title**)
                $generatedTitle = preg_replace('/[_\*~`]+/', '', $generatedTitle);
                $generatedTitle = \Illuminate\Support\Str::limit($generatedTitle, 50);
            }

            $conversation->title = $generatedTitle;
            $conversation->save();

            return response()->json(['success' => true, 'new_title' => $generatedTitle]);

        } catch (RequestException $e) {
            Log::error('Gemini API Request Exception for title generation: ' . $e->getMessage(), ['response_body' => $e->response ? $e->response->body() : 'No response body']);
            return response()->json(['error' => 'API Error generating title: ' . $e->getMessage()], $e->response ? $e->response->status() : 500);
        } catch (\Exception $e) {
            Log::error('An unexpected error occurred during title generation: ' . $e->getMessage());
            return response()->json(['error' => 'An unexpected server error occurred during title generation: ' . $e->getMessage()], 500);
        }
    }
}
