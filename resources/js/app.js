import './bootstrap';

// Import Alpine.js
import Alpine from 'alpinejs';

// Import Choices.js
import Choices from 'choices.js';
import 'choices.js/public/assets/styles/choices.min.css';

// Make Choices available globally
window.Choices = Choices;

// Initialize Alpine.js
window.Alpine = Alpine;
Alpine.start();
