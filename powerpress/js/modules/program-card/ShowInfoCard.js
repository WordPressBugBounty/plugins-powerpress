/**
 * ShowInfoCard Module
 * handles display and updates for the show info card (title, artwork, author, description, episode count)
 * note: program selector is managed by ProgramCardManager
 */

// ============================================
//              SHOW INFO CARD
// ============================================

const ShowInfoCard = {

  // ==========================================
  //              CONFIGURATION
  // ==========================================

  /** @type {string} */
  containerSelector: "#pp-show-info-card, .prog-sum-head",

  /** @type {Object.<string, string>} */
  formFields: {
    title: 'input[name="Feed[title]"]',
    author: 'input[name="Feed[itunes_talent_name]"]',
    description: 'input[name="Feed[description]"]',
    image: 'input[name="Feed[itunes_image]"]',
  },

  // ==========================================
  //              GETTERS
  // ==========================================

  /**
   * @returns {HTMLElement|null}
   */
  getContainer() { return document.querySelector(this.containerSelector); },

  /**
   * @param {string} fieldName - key from formFields
   * @returns {string|null}
   */
  getFormFieldValue(fieldName) {
    const selector = this.formFields[fieldName];
    if (!selector) return null;
    const field = document.querySelector(selector);
    return field ? field.value : null;
  },

  /**
   * @returns {{title: string|null, author: string|null, description: string|null, image: string|null}}
   */
  getFormData() {
    return {
      title: this.getFormFieldValue("title"),
      author: this.getFormFieldValue("author"),
      description: this.getFormFieldValue("description"),
      image: this.getFormFieldValue("image"),
    };
  },

  // ==========================================
  //              UPDATE METHODS
  // ==========================================

  /**
   * @param {Object} programInfo - title, image, author, description, episode_count, category, created_date, last_update
   */
  update(programInfo) {
    if (!programInfo) return;
    const container = this.getContainer();
    if (!container) return;

    // 1) UPDATE CORE FIELDS
    this._updateTitle(programInfo);
    this._updateImage(programInfo);
    this._updateAuthor(container, programInfo);
    this._updateDescription(container, programInfo);
    this._updateEpisodeCount(container, programInfo);

    // 2) UPDATE METADATA FIELDS
    this._updateCategory(container, programInfo);
    const createdEl = this._updateCreatedDate(container, programInfo);
    const lastUpdateEl = this._updateLastUpdate(container, programInfo);

    // 3) UPDATE DATE SEPARATOR VISIBILITY
    const separatorEl = container.querySelector(".pp-program-card__date-separator");
    if (separatorEl) {
      const hasLastUpdate = programInfo.last_update && lastUpdateEl && lastUpdateEl.style.display !== "none";
      const hasCreatedDate = programInfo.created_date && createdEl && createdEl.style.display !== "none";
      separatorEl.style.display = hasLastUpdate && hasCreatedDate ? "" : "none";
    }
  },

  /** @param {Object} info */
  _updateTitle(info) {
    const titleEl = document.getElementById("welcome-title");
    if (titleEl && info.title !== undefined)
      titleEl.textContent = info.title;
  },

  /** @param {Object} info */
  _updateImage(info) {
    const imageEl = document.getElementById("welcome-preview-image");
    if (imageEl && info.image)
      imageEl.src = info.image;
  },

  /**
   * @param {HTMLElement} container
   * @param {Object} info
   */
  _updateAuthor(container, info) {
    const authorEl = container.querySelector(".pp-program-card__author");
    if (authorEl && info.author !== undefined) {
      const labelPrefix = this.extractLabelPrefix(authorEl.textContent) || "By: ";
      authorEl.textContent = labelPrefix + info.author;
    }
  },

  /**
   * @param {HTMLElement} container
   * @param {Object} info
   */
  _updateDescription(container, info) {
    const descEl = container.querySelector(".pp-program-card__description");
    if (descEl && info.description !== undefined)
      descEl.textContent = info.description;
  },

  /**
   * @param {HTMLElement} container
   * @param {Object} info
   */
  _updateEpisodeCount(container, info) {
    const episodeCountEl = container.querySelector(".pp-program-card__episode-count h2, .pp-program-card__episode-count .pp-heading");
    if (episodeCountEl && info.episode_count !== undefined)
      episodeCountEl.textContent = parseInt(info.episode_count, 10);
  },

  /**
   * @param {HTMLElement} container
   * @param {Object} info
   */
  _updateCategory(container, info) {
    const categoryEl = container.querySelector(".pp-program-card__category");
    if (!categoryEl) return;
    if (info.category) {
      const strongEl = categoryEl.querySelector("strong");
      const label = strongEl ? strongEl.textContent : "Category:";
      categoryEl.innerHTML = "<strong>" + this.escapeHtml(label) + "</strong> " + this.escapeHtml(info.category);
      categoryEl.style.display = "";
    } else {
      categoryEl.style.display = "none";
    }
  },

  /**
   * @param {HTMLElement} container
   * @param {Object} info
   * @returns {HTMLElement|null}
   */
  _updateCreatedDate(container, info) {
    const createdEl = container.querySelector(".pp-program-card__created-date");
    if (!createdEl) return null;
    if (info.created_date) {
      const strongEl = createdEl.querySelector("strong");
      const label = strongEl ? strongEl.textContent : "Started Publishing:";
      createdEl.innerHTML = "<strong>" + this.escapeHtml(label) + "</strong> " + this.escapeHtml(this.formatDate(info.created_date));
      createdEl.style.display = "";
    } else {
      createdEl.style.display = "none";
    }
    return createdEl;
  },

  /**
   * @param {HTMLElement} container
   * @param {Object} info
   * @returns {HTMLElement|null}
   */
  _updateLastUpdate(container, info) {
    const lastUpdateEl = container.querySelector(".pp-program-card__last-update");
    if (!lastUpdateEl) return null;
    if (info.last_update) {
      const strongEl = lastUpdateEl.querySelector("strong");
      const label = strongEl ? strongEl.textContent : "Last Upload:";
      lastUpdateEl.innerHTML = "<strong>" + this.escapeHtml(label) + "</strong> " + this.escapeHtml(this.formatDate(info.last_update));
      lastUpdateEl.style.display = "";
    } else {
      lastUpdateEl.style.display = "none";
    }
    return lastUpdateEl;
  },

  // ==========================================
  //              HELPERS
  // ==========================================

  /**
   * extracts label prefix from text ("By John Doe" -> "By ")
   * @param {string} text
   * @returns {string|null}
   */
  extractLabelPrefix(text) {
    if (!text) return null;
    const match = text.match(/^([^a-zA-Z]*[a-zA-Z]+\s)/);
    return match ? match[1] : null;
  },

  /**
   * @param {string} dateStr
   * @returns {string}
   */
  formatDate(dateStr) {
    if (!dateStr) return "";
    try {
      return new Date(dateStr).toLocaleDateString();
    } catch (e) {
      return dateStr;
    }
  },

  /**
   * @param {string} html
   */
  replaceHtml(html) {
    const container = this.getContainer();
    if (container && html)
      container.outerHTML = html;
  },

  /**
   * @param {string} text
   * @returns {string}
   */
  escapeHtml(text) {
    const div = document.createElement("div");
    div.textContent = text;
    return div.innerHTML;
  },

  /**
   * @param {string} text
   * @returns {string}
   */
  escapeAttr(text) {
    return String(text)
      .replace(/&/g, "&amp;")
      .replace(/"/g, "&quot;")
      .replace(/'/g, "&#39;")
      .replace(/</g, "&lt;")
      .replace(/>/g, "&gt;");
  },
};

export { ShowInfoCard };
