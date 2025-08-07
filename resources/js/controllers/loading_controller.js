import { Controller } from "@hotwired/stimulus";

export default class extends Controller {
  static targets = ["loader", "progressBar"];

  connect() {
    console.log("Loading controller connected");
  }

  show() {
    this.loaderTarget.classList.remove("hidden");
    this.progressBarTarget.style.width = '0%';
    let width = 0;
    this.interval = setInterval(() => {
      if (width < 90) {
        width += 5; // Simulate progress
        this.progressBarTarget.style.width = `${width}%`;
      } else {
        clearInterval(this.interval);
      }
    }, 100); // Update every 100ms
  }

  hide() {
    clearInterval(this.interval);
    this.progressBarTarget.style.width = '100%';
    setTimeout(() => {
      this.loaderTarget.classList.add("hidden");
      this.progressBarTarget.style.width = '0%'; // Reset for next time
    }, 200); // A small delay to show 100% before hiding
  }
}