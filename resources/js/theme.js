(()=>{
 const key='metjari-theme';let saved;try{saved=localStorage.getItem(key)}catch{}
 const media=window.matchMedia('(prefers-color-scheme: dark)');
 const apply=t=>{document.documentElement.dataset.theme=t;const b=document.getElementById('theme-toggle');if(b){b.firstElementChild.textContent=t==='dark'?'☀':'☾';b.setAttribute('aria-label',t==='dark'?'تفعيل المظهر الفاتح':'تفعيل المظهر الداكن');b.setAttribute('aria-pressed',String(t==='dark'))}document.querySelector('meta[name="theme-color"]')?.setAttribute('content',t==='dark'?'#091d36':'#f5b900')};
 apply(saved==='light'||saved==='dark'?saved:media.matches?'dark':'light');
 document.addEventListener('DOMContentLoaded',()=>{apply(document.documentElement.dataset.theme);document.getElementById('theme-toggle')?.addEventListener('click',()=>{saved=document.documentElement.dataset.theme==='dark'?'light':'dark';apply(saved);try{localStorage.setItem(key,saved)}catch{}})});
 media.addEventListener('change',e=>{if(!saved)apply(e.matches?'dark':'light')});
})();
