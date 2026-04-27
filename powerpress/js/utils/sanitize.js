/**
 * Sanitization Utility Functions for PowerPress
 * shared functions for preventing XSS and sanitizing user/server content
 */

/**
 * sanitizes html string to prevent xss attacks
 * removes script tags, event handlers, and dangerous attributes
 * @param {string} html - raw html string
 * @returns {string} sanitized html
 */
export function sanitizeHtml(html) {
  if (!html || typeof html !== "string") return "";

  const temp = document.createElement("div");
  temp.innerHTML = html;

  // remove script tags
  temp.querySelectorAll("script").forEach((el) => el.remove());

  // remove style tags (can contain expressions in older browsers)
  temp.querySelectorAll("style").forEach((el) => el.remove());

  // dangerous event handler attributes
  const dangerousAttrs = [
    "onclick", "ondblclick", "onmousedown", "onmouseup", "onmouseover",
    "onmousemove", "onmouseout", "onmouseenter", "onmouseleave",
    "onkeydown", "onkeypress", "onkeyup",
    "onfocus", "onblur", "onchange", "onsubmit", "onreset",
    "onload", "onerror", "onabort", "onunload", "onresize", "onscroll"
  ];

  temp.querySelectorAll("*").forEach((el) => {
    // remove event handlers
    dangerousAttrs.forEach((attr) => el.removeAttribute(attr));

    // remove javascript: urls from href/src/action
    ["href", "src", "action", "formaction", "xlink:href"].forEach((attr) => {
      const val = el.getAttribute(attr);
      if (val && val.toLowerCase().trim().startsWith("javascript:")) {
        el.removeAttribute(attr);
      }
    });

    // remove data: urls from src (potential for script injection)
    const src = el.getAttribute("src");
    if (src && src.toLowerCase().trim().startsWith("data:") &&
        !src.toLowerCase().includes("data:image/")) {
      el.removeAttribute("src");
    }
  });

  return temp.innerHTML;
}

/**
 * escapes html special characters for safe text display
 * @param {string} text - raw text that may contain html chars
 * @returns {string} escaped text safe for innerHTML
 */
export function escapeHtml(text) {
  if (!text || typeof text !== "string") return "";

  const map = {
    "&": "&amp;",
    "<": "&lt;",
    ">": "&gt;",
    '"': "&quot;",
    "'": "&#039;"
  };

  return text.replace(/[&<>"']/g, (char) => map[char]);
}

/**
 * safely sets element text content (preferred over innerHTML for text)
 * @param {HTMLElement} element - target element
 * @param {string} text - text to set
 */
export function safeSetText(element, text) {
  if (element) {
    element.textContent = text || "";
  }
}
