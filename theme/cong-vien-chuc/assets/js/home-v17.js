/*
 * Trang chủ v17 (16/10/2026) - JS từ file thiết kế, nối dữ liệu thật:
 * - Tìm kiếm -> /tim-kiem/?q=
 * - "Việc làm quanh bạn": tỉnh/thành lấy từ backend (34 tỉnh), ước lượng theo IP
 *   (ipapi.co, như thiết kế) + quy đổi tên tỉnh cũ sang tỉnh mới sau sáp nhập,
 *   tải tin đang nhận hồ sơ của tỉnh qua admin-ajax (cvc_home_jobs).
 * - AI Coach: hỏi đáp thật qua admin-ajax (cvc_ai_coach) -> API /api/ai/coach.
 */
(function(){
 const CFG=window.CVC_HOME||{};
 // Menu đầu trang: assets/js/site-chrome.js (dùng chung toàn site).

 function esc(s){const d=document.createElement('div');d.textContent=s==null?'':String(s);return d.innerHTML;}
 function post(action,data){
  const fd=new FormData();fd.append('action',action);fd.append('nonce',CFG.nonce||'');
  Object.keys(data||{}).forEach(k=>fd.append(k,typeof data[k]==='object'?JSON.stringify(data[k]):data[k]));
  return fetch(CFG.ajaxUrl,{method:'POST',body:fd,credentials:'same-origin'}).then(r=>r.json());
 }
 function fold(s){return String(s||'').normalize('NFD').replace(/[̀-ͯ]/g,'').replace(/đ/g,'d').replace(/Đ/g,'D').toLowerCase().replace(/^(thanh pho|tinh|tp\.?)\s+/,'').replace(/[^a-z0-9]+/g,' ').trim();}

 /* ---------- Việc làm quanh bạn ---------- */
 const regionLabel=document.getElementById('currentRegion');
 const regionStatus=document.getElementById('locationStatus');
 const regionSelect=document.getElementById('regionSelect');
 const localJobs=document.getElementById('localJobs');
 const provinces=Array.isArray(CFG.provinces)?CFG.provinces:[];
 // Tỉnh/thành cũ (trước sáp nhập 2025) -> tỉnh/thành mới, để khớp vị trí IP.
 const merged={'ha giang':'tuyen quang','yen bai':'lao cai','bac kan':'thai nguyen','vinh phuc':'phu tho','hoa binh':'phu tho','bac giang':'bac ninh','thai binh':'hung yen','hai duong':'hai phong','ha nam':'ninh binh','nam dinh':'ninh binh','quang binh':'quang tri','thua thien hue':'hue','quang nam':'da nang','kon tum':'quang ngai','binh dinh':'gia lai','ninh thuan':'khanh hoa','phu yen':'dak lak','dak nong':'lam dong','binh thuan':'lam dong','binh phuoc':'dong nai','binh duong':'ho chi minh','ba ria vung tau':'ho chi minh','long an':'tay ninh','tien giang':'dong thap','ben tre':'vinh long','tra vinh':'vinh long','kien giang':'an giang','soc trang':'can tho','hau giang':'can tho','bac lieu':'ca mau','hanoi':'ha noi','ho chi minh city':'ho chi minh','saigon':'ho chi minh','da nang city':'da nang','hai phong city':'hai phong','can tho city':'can tho'};
 function findProvince(raw){
  let k=fold(raw).replace(/\s+(province|city)$/,'');
  if(merged[k])k=merged[k];
  return provinces.find(p=>fold(p.short)===k||fold(p.name)===k)||null;
 }
 function jobsUrl(p){return (CFG.recruitmentsUrl||'/')+(p?('?tinh='+encodeURIComponent(p.id)):'');}
 function renderEmpty(p){
  const others=provinces.filter(x=>x.open_recruitments>0&&(!p||x.id!==p.id)).sort((a,b)=>b.open_recruitments-a.open_recruitments).slice(0,3);
  return '<div class="side-job"><span class="tag">Khu vực ưu tiên</span><h4>Chưa có thông báo đang nhận hồ sơ'+(p?' tại '+esc(p.short):'')+'</h4><p>Hệ thống cập nhật tin từ cổng thông tin chính thức mỗi ngày.</p><a href="'+esc(CFG.recruitmentsUrl)+'">Xem tất cả tin tuyển dụng →</a></div>'+
   (others.length?'<div class="side-job"><span class="tag">Khu vực có tin mới</span><h4>'+others.map(o=>esc(o.short)+' ('+o.open_recruitments+')').join(' · ')+'</h4><p>Mở rộng phạm vi tìm kiếm để không bỏ lỡ cơ hội phù hợp.</p><a href="'+esc(jobsUrl(others[0]))+'">Xem tin tại '+esc(others[0].short)+' →</a></div>':'');
 }
 function renderRegion(p){
  if(!localJobs)return;
  regionLabel.textContent=p?p.short:'Chọn tỉnh/thành';
  if(!p){localJobs.innerHTML=renderEmpty(null);return;}
  if(!p.open_recruitments){localJobs.innerHTML=renderEmpty(p);return;}
  localJobs.innerHTML='<div class="side-loading">Đang tải tin tuyển dụng tại '+esc(p.short)+'…</div>';
  post('cvc_home_jobs',{province_id:p.id}).then(res=>{
   const list=(res&&res.success&&res.data&&res.data.items)||[];
   if(!list.length){localJobs.innerHTML=renderEmpty(p);return;}
   localJobs.innerHTML=list.slice(0,3).map(j=>'<div class="side-job"><span class="tag">'+esc(j.agency||('Tuyển dụng tại '+p.short))+'</span><h4>'+esc(j.title)+'</h4><div class="side-meta">'+(j.deadline?'<span>Hạn nộp <b>'+esc(j.deadline)+'</b></span>':'')+(j.positions?'<span>'+esc(j.positions)+' chỉ tiêu</span>':'')+'</div><a href="'+esc(j.url)+'">Xem thông báo →</a></div>').join('')+
    (p.open_recruitments>list.length?'<div class="side-job"><a href="'+esc(jobsUrl(p))+'">Xem tất cả '+p.open_recruitments+' tin tại '+esc(p.short)+' →</a></div>':'');
  }).catch(()=>{localJobs.innerHTML=renderEmpty(p);});
 }
 function remember(id){try{localStorage.setItem('cvc-home-region',String(id));}catch(e){}}
 if(regionSelect){regionSelect.addEventListener('change',()=>{const p=provinces.find(x=>String(x.id)===regionSelect.value);if(p){regionStatus.textContent='Đang xem theo lựa chọn của bạn.';remember(p.id);renderRegion(p);}})}
 if(regionLabel&&regionStatus&&localJobs){
  let saved=null;try{saved=localStorage.getItem('cvc-home-region');}catch(e){}
  const savedP=saved?provinces.find(x=>String(x.id)===saved):null;
  if(savedP){regionSelect.value=String(savedP.id);regionStatus.textContent='Theo khu vực bạn đã chọn trước đó.';renderRegion(savedP);}
  else if(window.fetch){
   fetch('https://ipapi.co/json/').then(r=>{if(!r.ok)throw new Error('IP lookup unavailable');return r.json()}).then(data=>{
    if(data&&data.country_code&&data.country_code!=='VN')throw new Error('outside');
    const p=findProvince(data.region||'')||findProvince(data.city||'');
    if(p){regionStatus.textContent='Khu vực ước lượng theo IP. Bạn có thể đổi bên dưới.';regionSelect.value=String(p.id);renderRegion(p);}
    else {regionLabel.textContent='Chọn tỉnh/thành';regionStatus.textContent='Không xác định được khu vực qua IP. Vui lòng chọn thủ công.';localJobs.innerHTML=renderEmpty(null);}
   }).catch(()=>{regionLabel.textContent='Chọn tỉnh/thành';regionStatus.textContent='Không lấy được vị trí IP. Vui lòng chọn tỉnh/thành thủ công.';localJobs.innerHTML=renderEmpty(null);});
  }
 }

 /* ---------- Tìm kiếm ---------- */
 const form=document.getElementById('siteSearch'),input=document.getElementById('searchInput'),toast=document.getElementById('toast');
 function showToast(msg){toast.textContent=msg;toast.classList.add('show');window.setTimeout(()=>toast.classList.remove('show'),2600)}
 if(form){form.addEventListener('submit',e=>{const q=(input.value||'').trim();if(!q){e.preventDefault();input.focus();showToast('Hãy nhập từ khóa bạn muốn tìm.');}});}

 /* ---------- AI Coach (hỏi đáp thật) ---------- */
 const aiLauncher=document.getElementById('aiChatLauncher');
 const aiPanel=document.getElementById('aiChatPanel');
 const aiClose=document.getElementById('aiChatClose');
 const aiMessages=document.getElementById('aiChatMessages');
 const aiForm=document.getElementById('aiChatForm');
 const aiInput=document.getElementById('aiChatInput');
 const history=[];let busy=false;
 function aiAddMessage(text,who,links){
  const bubble=document.createElement('div');bubble.className='ai-msg '+(who||'bot');
  bubble.textContent=text;
  if(links&&links.length){
   const box=document.createElement('div');box.className='ai-chat-links';
   links.forEach(l=>{const a=document.createElement('a');a.className='ai-chat-link';a.href=(l.url||'').indexOf('http')===0?l.url:(CFG.homeUrl||'/').replace(/\/$/,'')+l.url;a.textContent=l.label+' →';box.appendChild(a);});
   bubble.appendChild(box);
  }
  aiMessages.appendChild(bubble);aiMessages.scrollTop=aiMessages.scrollHeight;return bubble;
 }
 function aiSend(text){
  const value=(text||'').trim();if(!value||busy)return;
  aiAddMessage(value,'user');aiInput.value='';busy=true;
  const typing=aiAddMessage('AI Coach đang soạn câu trả lời…','bot typing');
  post('cvc_ai_coach',{message:value,history:history.slice(-4)}).then(res=>{
   typing.remove();
   if(!res||!res.success){aiAddMessage((res&&res.data&&res.data.message)||'Chưa trả lời được lúc này, vui lòng thử lại sau.','bot');return;}
   const d=res.data||{};aiAddMessage(d.answer||'','bot',d.links||[]);
   history.push({role:'user',text:value});history.push({role:'bot',text:(d.answer||'').slice(0,600)});
  }).catch(()=>{typing.remove();aiAddMessage('Lỗi kết nối, vui lòng thử lại.','bot');}).finally(()=>{busy=false;});
 }
 function aiOpen(){aiPanel.classList.add('open');aiLauncher.setAttribute('aria-expanded','true');aiInput.focus();}
 function aiDismiss(){aiPanel.classList.remove('open');aiLauncher.setAttribute('aria-expanded','false');aiLauncher.focus();}
 if(aiLauncher&&aiPanel){
  aiLauncher.addEventListener('click',()=>aiPanel.classList.contains('open')?aiDismiss():aiOpen());
  aiClose.addEventListener('click',aiDismiss);
  aiForm.addEventListener('submit',e=>{e.preventDefault();aiSend(aiInput.value)});
  document.querySelectorAll('[data-prompt]').forEach(b=>b.addEventListener('click',()=>aiSend(b.getAttribute('data-prompt'))));
  document.querySelectorAll('[data-open-ai]').forEach(b=>b.addEventListener('click',e=>{e.preventDefault();aiOpen();}));
  document.addEventListener('keydown',e=>{if(e.key==='Escape'&&aiPanel.classList.contains('open'))aiDismiss()});
 }

 /* ---------- Số liệu chạy số khi xuất hiện ---------- */
 const statCounters=[...document.querySelectorAll('.stat-counter[data-target]')];
 function runStatCounter(el){
  if(el.dataset.counted==='true')return;
  el.dataset.counted='true';
  const target=Number(el.dataset.target||0);
  const suffix=el.dataset.suffix||'';
  const reduceMotion=window.matchMedia&&window.matchMedia('(prefers-reduced-motion: reduce)').matches;
  if(reduceMotion){el.textContent=target.toLocaleString('vi-VN')+suffix;return;}
  const duration=1350;
  const start=performance.now();
  function tick(now){
   const progress=Math.min(1,(now-start)/duration);
   const eased=1-Math.pow(1-progress,3);
   el.textContent=Math.round(target*eased).toLocaleString('vi-VN')+suffix;
   if(progress<1)requestAnimationFrame(tick);
  }
  el.textContent='0'+suffix;
  requestAnimationFrame(tick);
 }
 if('IntersectionObserver' in window && statCounters.length){
  const statObserver=new IntersectionObserver(entries=>{
   entries.forEach(entry=>{
    if(entry.isIntersecting){
     statCounters.forEach(runStatCounter);
     statObserver.disconnect();
    }
   });
  },{threshold:.28});
  const statsBlock=document.querySelector('.stats');
  if(statsBlock)statObserver.observe(statsBlock);
 }else{statCounters.forEach(runStatCounter);}
})();
