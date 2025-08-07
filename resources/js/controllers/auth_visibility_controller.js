import { Controller } from "@hotwired/stimulus"

export default class extends Controller {
    static targets = ["authForm", "registerButton", "registerButtonText", "registerButtonSpinner", "loginButton", "loginButtonText", "loginButtonSpinner"]
    static values = {
        user: Boolean,
        isRegistering: Boolean,
        isLoggingIn: Boolean,
    }

    connect() {
    }

    get isRegistering() { return this.isRegisteringValue; }
    set isRegistering(value) {
        this.isRegisteringValue = value;
        this.registerButtonTarget.disabled = value;
        this.registerButtonTextTarget.hidden = value;
        this.registerButtonSpinnerTarget.hidden = !value;
    }

    get isLoggingIn() { return this.isLoggingInValue; }
    set isLoggingIn(value) {
        this.isLoggingInValue = value;
        this.loginButtonTarget.disabled = value;
        this.loginButtonTextTarget.hidden = value;
        this.loginButtonSpinnerTarget.hidden = !value;
    }

    // Intercept form submissions to show loading indicator
    // This assumes your forms are directly submitting
    // If using fetch, you'd integrate this into your fetch logic
    submitRegisterForm(event) {
        this.isRegistering = true;
    }

    submitLoginForm(event) {
        this.isLoggingIn = true;
    }
}