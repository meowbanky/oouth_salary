/**
 * Theme Manager - Dark Mode Implementation
 * Handles theme switching, persistence, and system preference detection
 */

class ThemeManager {
  constructor() {
    this.currentTheme = "light";
    this.themeKey = "oouth-theme";
    this.systemThemeQuery = window.matchMedia("(prefers-color-scheme: dark)");

    this.init();
  }

  init() {
    // Load saved theme or detect system preference
    this.loadTheme();

    // Listen for system theme changes
    this.systemThemeQuery.addEventListener("change", (e) => {
      if (!localStorage.getItem(this.themeKey)) {
        this.setTheme(e.matches ? "dark" : "light");
      }
    });

    // Create theme toggle button
    this.createThemeToggle();
  }

  loadTheme() {
    // Check for saved theme preference
    const savedTheme = localStorage.getItem(this.themeKey);

    if (savedTheme) {
      this.setTheme(savedTheme);
    } else {
      // Use system preference if no saved preference
      this.setTheme(this.systemThemeQuery.matches ? "dark" : "light");
    }
  }

  setTheme(theme) {
    this.currentTheme = theme;
    document.documentElement.setAttribute("data-theme", theme);
    localStorage.setItem(this.themeKey, theme);

    // Update theme toggle button icon
    this.updateToggleIcon();

    // Dispatch custom event for other components
    document.dispatchEvent(
      new CustomEvent("themeChanged", {
        detail: { theme: theme },
      })
    );
  }

  toggleTheme() {
    const newTheme = this.currentTheme === "light" ? "dark" : "light";
    this.setTheme(newTheme);
  }

  createThemeToggle() {
    // Check if toggle already exists
    if (document.getElementById("theme-toggle")) {
      return;
    }

    const toggle = document.createElement("button");
    toggle.id = "theme-toggle";
    toggle.className = "theme-toggle";
    toggle.setAttribute("aria-label", "Toggle theme");
    toggle.setAttribute("title", "Toggle dark/light mode");

    // Add to header if it exists, otherwise create a floating toggle
    const header = document.querySelector("header, .header, .navbar");
    if (header) {
      // Find a good spot in the header (usually near user menu or logo)
      const userMenu = header.querySelector(
        ".user-menu, .navbar-nav, .dropdown"
      );
      if (userMenu) {
        userMenu.parentNode.insertBefore(toggle, userMenu);
      } else {
        header.appendChild(toggle);
      }
    } else {
      // Create floating toggle if no header found
      toggle.style.position = "fixed";
      toggle.style.top = "20px";
      toggle.style.right = "20px";
      toggle.style.zIndex = "9999";
      toggle.style.boxShadow = "0 4px 6px -1px rgb(0 0 0 / 0.1)";
      document.body.appendChild(toggle);
    }

    // Add click event
    toggle.addEventListener("click", () => {
      this.toggleTheme();
    });

    // Set initial icon
    this.updateToggleIcon();
  }

  updateToggleIcon() {
    const toggle = document.getElementById("theme-toggle");
    if (!toggle) return;

    const icon =
      this.currentTheme === "dark"
        ? this.createSunIcon()
        : this.createMoonIcon();

    toggle.innerHTML = "";
    toggle.appendChild(icon);
  }

  createSunIcon() {
    const svg = document.createElementNS("http://www.w3.org/2000/svg", "svg");
    svg.setAttribute("fill", "none");
    svg.setAttribute("viewBox", "0 0 24 24");
    svg.setAttribute("stroke", "currentColor");
    svg.setAttribute("stroke-width", "2");
    svg.setAttribute("stroke-linecap", "round");
    svg.setAttribute("stroke-linejoin", "round");

    const circle = document.createElementNS(
      "http://www.w3.org/2000/svg",
      "circle"
    );
    circle.setAttribute("cx", "12");
    circle.setAttribute("cy", "12");
    circle.setAttribute("r", "5");

    const path = document.createElementNS("http://www.w3.org/2000/svg", "path");
    path.setAttribute(
      "d",
      "M12 1v2m0 18v2M4.22 4.22l1.42 1.42m12.72 12.72l1.42 1.42M1 12h2m18 0h2M4.22 19.78l1.42-1.42M18.36 5.64l1.42-1.42"
    );

    svg.appendChild(circle);
    svg.appendChild(path);

    return svg;
  }

  createMoonIcon() {
    const svg = document.createElementNS("http://www.w3.org/2000/svg", "svg");
    svg.setAttribute("fill", "none");
    svg.setAttribute("viewBox", "0 0 24 24");
    svg.setAttribute("stroke", "currentColor");
    svg.setAttribute("stroke-width", "2");
    svg.setAttribute("stroke-linecap", "round");
    svg.setAttribute("stroke-linejoin", "round");

    const path = document.createElementNS("http://www.w3.org/2000/svg", "path");
    path.setAttribute("d", "M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z");

    svg.appendChild(path);

    return svg;
  }

  // Public methods for external use
  getCurrentTheme() {
    return this.currentTheme;
  }

  isDarkMode() {
    return this.currentTheme === "dark";
  }

  // Method to apply theme to dynamically loaded content
  applyThemeToElement(element) {
    if (element) {
      element.setAttribute("data-theme", this.currentTheme);
    }
  }
}

// Auto-initialize when DOM is ready
document.addEventListener("DOMContentLoaded", () => {
  window.themeManager = new ThemeManager();
});

// Export for module usage
if (typeof module !== "undefined" && module.exports) {
  module.exports = ThemeManager;
}
