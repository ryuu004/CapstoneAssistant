// logout_controller.js
import { Controller } from "@hotwired/stimulus"

export default class extends Controller {
  static values = {
    isLoggingOut: Boolean,
  }

  connect() {
    // isLoggingOutValue defaults to false for Boolean, so no explicit initialization needed here.
    // Ensure initial state is applied after controller connects and elements are available
    this.updateLogoutButtonState(this.isLoggingOutValue);
  }

  get isLoggingOut() { return this.isLoggingOutValue; }
  set isLoggingOut(value) {
    this.isLoggingOutValue = value;
    this.updateLogoutButtonState(value);
  }

  updateLogoutButtonState(value) {
    const logoutButton = this.element.querySelector('[data-logout-target="logoutButton"]');
    const logoutButtonText = this.element.querySelector('[data-logout-target="logoutButtonText"]');
    const logoutButtonSpinner = this.element.querySelector('[data-logout-target="logoutButtonSpinner"]');

    if (logoutButton) {
      logoutButton.disabled = value;
    }
    if (logoutButtonText) {
      logoutButtonText.hidden = value;
    }
    if (logoutButtonSpinner) {
      logoutButtonSpinner.hidden = !value;
    }
  }

  logout(event) {
    event.preventDefault(); // Prevent default form submission
    this.isLoggingOut = true; // Show loading indicator

    fetch("/logout", {
      method: "POST",
      headers: {
        "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]').content,
        "Accept": "application/json",
        "Content-Type": "application/json",
      },
    }).then(response => {
      if (response.ok) {
        window.location.href = "/auth"; // Redirect to login or homepage
      }
    }).catch(error => {
      console.error('Error during logout:', error);
      // Handle error, maybe show an error message
    }).finally(() => {
      this.isLoggingOut = false; // Hide loading indicator regardless of success or failure
    });
  }
}
