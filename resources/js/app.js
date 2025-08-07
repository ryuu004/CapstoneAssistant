import './bootstrap';
import { Application, Controller } from "@hotwired/stimulus";
import GeminiAssistantController from './controllers/gemini_assistant_controller';

console.log('Stimulus Application Start Attempted');
const application = Application.start();
application.register("gemini-assistant", GeminiAssistantController);
console.log('Stimulus Application Registered');