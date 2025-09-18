
document.addEventListener('DOMContentLoaded', function(){
  // jobs search
  var jobSearch = document.getElementById('job-search');
  if(jobSearch){
    jobSearch.addEventListener('input', function(){
      var q = jobSearch.value.trim();
      var url = 'search_jobs.php?ajax=1&t=' + Date.now() + '&q=' + encodeURIComponent(q);
      fetch(url).then(r=>r.json()).then(data=>{
        var list = document.getElementById('jobs-list');
        if(!list) return;
        if(data.length===0) list.innerHTML='<p>لا توجد نتائج.</p>'; else {
          list.innerHTML='';
          data.forEach(function(it){
            var div = document.createElement('div'); div.className='job-card card';
            div.innerHTML = '<h3>'+escapeHtml(it.title)+'</h3><div class="meta">'+escapeHtml(it.company)+' • '+escapeHtml(it.location)+'</div><p>'+escapeHtml(it.description)+'</p>';
            var utype = (typeof window !== 'undefined' && window.USER_TYPE) ? window.USER_TYPE : 'guest';
            var isLogged = (typeof window !== 'undefined' && window.IS_LOGGED_IN) ? window.IS_LOGGED_IN : false;
            var canApply = (typeof window !== 'undefined' && window.CAN_APPLY) ? window.CAN_APPLY : false;
            if (utype === 'graduate') {
              if (it.has_applied) {
                div.innerHTML += '<div class="application-status"><span class="status-badge applied">✓ تم التقديم</span><p style="color: #666; font-size: 14px; margin: 5px 0;">لقد قدمت لهذه الوظيفة من قبل</p></div>';
              } else {
                if (canApply) {
                  div.innerHTML += '<a class="btn btn-apply" href="apply.php?job_id='+it.id+'">قدم الآن</a>';
                } else {
                  div.innerHTML += '<button class="btn" disabled title="بانتظار التحقق">بانتظار التحقق</button>';
                }
              }
            } else if (!isLogged) {
              div.innerHTML += '<a class="btn" href="login.php">دخول للتقديم</a>';
            }
            list.appendChild(div);
          });
        }
      });
    });
    document.getElementById('clear-search').addEventListener('click', function(){ jobSearch.value=''; jobSearch.dispatchEvent(new Event('input')); });
  }
  // grads search
  var gradSearch = document.getElementById('grad-search');
  if(gradSearch){
    gradSearch.addEventListener('input', function(){
      var q = gradSearch.value.trim();
      fetch('search_graduates.php?ajax=1&q='+encodeURIComponent(q)).then(r=>r.json()).then(data=>{
        var list = document.getElementById('grads-list');
        if(!list) return;
        if(data.length===0) list.innerHTML='<p>لا توجد نتائج.</p>'; else {
          list.innerHTML='';
          data.forEach(function(it){
            var div = document.createElement('div'); div.className='grad-card card';
            
            // Create verification badge
            var verificationBadge = '';
            if(it.is_verified == 1) {
              verificationBadge = '<span class="verification-badge verified">✓ محقق</span>';
            } else if(it.verification_status === 'pending') {
              verificationBadge = '<span class="verification-badge pending">⏳ قيد المراجعة</span>';
            } else {
              verificationBadge = '<span class="verification-badge unverified">غير محقق</span>';
            }
            
            div.innerHTML = '<div class="grad-header"><h3>'+escapeHtml(it.name)+'</h3>'+verificationBadge+'</div><div class="meta">'+escapeHtml(it.university)+' • '+escapeHtml(it.specialization)+'</div><p>الهاتف: '+escapeHtml(it.phone)+'</p>';
            if(it.cv_link) div.innerHTML += '<a class="btn btn-apply" href="'+escapeHtml(it.cv_link)+'" target="_blank">عرض السيرة (رابط)</a>';
            else if(it.cv_file) div.innerHTML += '<a class="btn btn-apply" href="'+escapeHtml(it.cv_file)+'" target="_blank">عرض السيرة (ملف)</a>';
            list.appendChild(div);
          });
        }
      });
    });
    document.getElementById('clear-grad').addEventListener('click', function(){ gradSearch.value=''; gradSearch.dispatchEvent(new Event('input')); });
  }
});

function escapeHtml(text){ var map = { '&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;','\'':'&#039;' }; return String(text).replace(/[&<>"']/g, function(m){ return map[m]; }); }
