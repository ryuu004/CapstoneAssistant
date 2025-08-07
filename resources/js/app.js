import './bootstrap';
import { Application } from '@hotwired/stimulus';
import { registerControllers } from 'stimulus-vite-helpers';

const application = Application.start();
const controllers = import.meta.glob('./controllers/**/*.js', { eager: true });
registerControllers(application, controllers);

// Expose the Stimulus application globally for debugging purposes
window.StimulusApplication = application;

window.callGeminiSaveApiKey = () => {
    const controllerElement = document.querySelector('[data-controller="gemini-assistant"]');
    if (controllerElement) {
        const controller = application.getControllerForElementAndIdentifier(controllerElement, 'gemini-assistant');
        if (controller && typeof controller.validateAndSaveApiKey === 'function') {
            console.log('Manually calling validateAndSaveApiKey from global function.');
            controller.validateAndSaveApiKey();
        } else {
            console.error('GeminiAssistantController or its validateAndSaveApiKey method not found.');
        }
    } else {
        console.error('Element with data-controller="gemini-assistant" not found.');
    }
};