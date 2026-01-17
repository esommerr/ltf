// js/messaging-button.js - FINAL VERSION
export function createMessageButton(options = {}) {
  const button = document.createElement('button');
  button.className = `message-button ${options.variant || 'contained'}`;
  button.innerHTML = `
    <span class="message-icon">✉️</span>
    <span class="button-text">${options.buttonText || 'Send Message'}</span>
  `;
  
  if (options.size) {
    button.classList.add(options.size);
  }
  
  button.addEventListener('click', function() {
    const professionalId = options.professionalId;
    
    if (!professionalId) {
      alert('Professional ID is required');
      return;
    }
    
    // Redirect to messages page to start conversation
    window.location.href = `messages.php?action=start&professional_id=${professionalId}`;
  });
  
  return button;
}
