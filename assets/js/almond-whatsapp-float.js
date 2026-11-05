/* Almond Systems floating WhatsApp CTA. No tracking SDK. */
(function(){
function ready(){
 if(document.querySelector('[data-almond-whatsapp]'))return;
 var css=document.createElement('style');
 css.textContent='.almond-whatsapp-float{position:fixed;right:20px;bottom:calc(20px + env(safe-area-inset-bottom,0px));z-index:10000;display:grid;place-items:center;width:58px;height:58px;border:2px solid white;border-radius:50%;background:#128c69;color:#fff;box-shadow:0 8px 24px rgba(0,0,0,.25);text-decoration:none;transition:transform .2s}.almond-whatsapp-float:hover{transform:translateY(-3px)}.almond-whatsapp-float:focus-visible{outline:3px solid #112b3e;outline-offset:4px}.almond-whatsapp-float svg{width:34px;height:34px}@media(max-width:600px){.almond-whatsapp-float{right:14px;bottom:calc(16px + env(safe-area-inset-bottom,0px));width:54px;height:54px}}';
 document.head.appendChild(css);
 var a=document.createElement('a');a.className='almond-whatsapp-float';
 a.setAttribute('data-almond-whatsapp','');a.setAttribute('aria-label','Chat with Almond Systems on WhatsApp');
 a.href='https://wa.me/2349168775034?text=Hello%20Almond%20Systems%2C%20I%20would%20like%20to%20discuss%20a%20project.';
 a.target='_blank';a.rel='noopener noreferrer';a.title='Chat with Almond Systems';
 a.innerHTML='<svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M20.4 11.7a8.4 8.4 0 0 1-12.4 7.4L3.1 20.5l1.4-4.9a8.4 8.4 0 1 1 15.9-3.9Z" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/><path d="M8.3 8.2c-.4.5-.5 1-.3 1.7.6 2.3 2.5 4.2 4.8 4.9.7.2 1.3.1 1.7-.3l1.1-1.1-2.2-1.2-.8 1c-1.4-.5-2.3-1.4-2.9-2.9l1-.8-1.2-2.2-1.2.9Z" fill="currentColor"/></svg>';
 document.body.appendChild(a);
}
if(document.readyState==='loading')document.addEventListener('DOMContentLoaded',ready,{once:true});else ready();
})();