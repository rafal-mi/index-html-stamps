const menu=document.querySelector('.menu-toggle');menu.addEventListener('click',()=>{const open=document.querySelector('.nav').classList.toggle('open');menu.setAttribute('aria-expanded',String(open));menu.textContent=open?'×':'☰'});document.querySelectorAll('.nav a').forEach(a=>a.addEventListener('click',()=>{document.querySelector('.nav').classList.remove('open');menu.setAttribute('aria-expanded','false');menu.textContent='☰'}));document.querySelectorAll('.filter').forEach(btn=>btn.addEventListener('click',()=>{document.querySelectorAll('.filter').forEach(b=>b.classList.toggle('active',b===btn));document.querySelectorAll('.product-card').forEach(card=>card.hidden=btn.dataset.filter!=='all'&&card.dataset.category!==btn.dataset.filter)}));document.getElementById('year').textContent=new Date().getFullYear();
const contactForm = document.getElementById('contact-form');
const contactStatus = document.getElementById('contact-status');
contactForm.addEventListener('submit', async (event) => {
  event.preventDefault();
  const submitButton = contactForm.querySelector('button[type="submit"]');
  submitButton.disabled = true;
  submitButton.textContent = 'Wysyłanie…';
  contactStatus.textContent = 'Trwa wysyłanie wiadomości…';
  contactStatus.setAttribute('role', 'status');

  try {
    const response = await fetch('send.php', {
      method: 'POST',
      body: new FormData(contactForm),
      headers: { 'Accept': 'application/json' }
    });
    const result = await response.json();
    if (!response.ok || !result.ok) {
      throw new Error(result.error || 'Nie udało się wysłać wiadomości.');
    }
    contactStatus.textContent = 'Wiadomość została wysłana. Dziękujemy!';
    contactForm.reset();
  } catch (error) {
    contactStatus.textContent = error instanceof Error
      ? error.message
      : 'Nie udało się wysłać wiadomości. Spróbuj ponownie.';
  } finally {
    submitButton.disabled = false;
    submitButton.textContent = 'Wyślij wiadomość';
  }
});
