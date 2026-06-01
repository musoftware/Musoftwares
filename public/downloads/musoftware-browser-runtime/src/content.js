function e(e,t){let n=[],r=document.evaluate(e,t||document,null,XPathResult.ORDERED_NODE_SNAPSHOT_TYPE,null);for(let e=0;e<r.snapshotLength;e++)n.push(r.snapshotItem(e));return n}var t={processNetworkEvent(e){if(!e||!e.data)return null;let{data:t,requestBody:n}=e,r=null,i=null,a=t.data?.node||t.data?.group||t.data;if(a?.new_forum_members||a?.new_members||a?.people_profiles||a?.members)r=`facebook.graphql.group_members`,i=a;else if(a?.comet_ufi_summary_and_actions_from_feedback?.comments||a?.comments)r=`facebook.graphql.comments`,i=a.comet_ufi_summary_and_actions_from_feedback?.comments||a.comments;else if(a?.reactors)r=`facebook.graphql.likes`,i=a.reactors;else return null;return{event:r,payload:i,requestBody:n}},async executeCommand(t){switch(t.action){case`navigate`:return t.target?.url?(window.location.href=t.target.url,{success:!0,navigating:!0}):{success:!1,error:`No target URL provided`};case`execute_graphql`:if(!t.requestBody)return{success:!1,error:`No requestBody provided`};try{let e=`${window.location.origin}/api/graphql/`,n=new XMLHttpRequest;return n.open(`POST`,e,!0),n.setRequestHeader(`Content-Type`,`application/x-www-form-urlencoded`),n.withCredentials=!0,n.onreadystatechange=function(){if(n.readyState===4)if(n.status===200)try{let e=n.responseText.replace(/^for\s*\(;+\);?/gm,``).trim().split(`
`).filter(e=>e.trim().length>0);for(let n of e){let e=n.replace(/^for\s*\(;+\);?/,``).trim();if(e.startsWith(`{`))try{let n=JSON.parse(e);t._streamResultFn&&t._streamResultFn(n,t.requestBody)}catch{}}}catch(e){console.error(`[FacebookAdapter] Failed to parse GraphQL response:`,e)}else console.error(`[FacebookAdapter] GraphQL fetch failed with status:`,n.status)},n.onerror=function(){console.error(`[FacebookAdapter] GraphQL XHR network error`)},n.send(t.requestBody),{success:!0,requested:!0}}catch(e){return{success:!1,error:e.message}}case`scroll_page`:return window.scrollBy({top:300,left:0,behavior:`smooth`}),document.querySelectorAll(`*[role="dialog"]`).forEach(e=>{e.querySelectorAll(`div`).forEach(e=>{e.scrollHeight>e.clientHeight&&e.clientHeight>0&&e.scrollBy({top:300,left:0,behavior:`smooth`})}),e.scrollHeight>e.clientHeight&&e.scrollBy({top:300,left:0,behavior:`smooth`})}),{success:!0,scrolled:!0};case`click_likes_dialog`:{let e=!1,t=e=>{let t=e.getBoundingClientRect();return t.width>0&&t.height>0&&t.top>=0&&t.top<=window.innerHeight&&t.bottom>=0&&t.left>=0&&t.left<=window.innerWidth&&t.right>=0},n=!1;if(document.querySelectorAll(`*[role="dialog"]`).forEach(e=>{e.querySelectorAll(`*[role="tab"]`).length>0&&(n=!0)}),!n){let n=document.querySelectorAll(`[aria-label]`),r=/(See who reacted|View reactions|reactions?|تعرف على الأشخاص|عرض التفاعلات|تفاعل|Like: \d+|Love: \d+|Care: \d+|Haha: \d+|Wow: \d+|Sad: \d+|Angry: \d+|أعجبني: \d+|أحببته: \d+|ادعمه: \d+|أضحكني: \d+|أدهشني: \d+|أحزنني: \d+|أغضبني: \d+)/i;for(let i of Array.from(n)){let n=(i.getAttribute(`aria-label`)||``).trim(),a=n.toLowerCase();if(!(a===`like`||a===`أعجبني`||a===`remove like`||a===`إلغاء الإعجاب`)&&r.test(n)&&t(i)&&!a.includes(`comment`)&&!a.includes(`تعليق`)&&!a.includes(`share`)&&!a.includes(`مشاركة`)){let t=i,n=i.querySelector(`[role="button"], [role="link"]`);n&&(t=n),t.click(),e=!0;break}}}let r=document.querySelectorAll(`*[role="tab"]`);for(let n of Array.from(r)){let r=(n.getAttribute(`aria-label`)||``).toLowerCase();if((r.includes(`all`)||r.includes(`الكل`)||r.includes(`peoplewho reacted`))&&t(n)){n.getAttribute(`aria-selected`)!==`true`&&(n.click(),e=!0);break}}return{success:!0,clicked:e}}case`scroll_dialogs`:{let e=document.querySelectorAll(`*[role="dialog"] div`),t=0;return e.forEach(e=>{e.scrollBy(0,99999),t++}),{success:!0,dialogs_scrolled:t}}case`scroll_reactions_dialog`:{let e=0;return document.querySelectorAll(`*[role="dialog"]`).forEach(t=>{let n=null,r=0;t.querySelectorAll(`div`).forEach(e=>{let t=e.scrollHeight-e.clientHeight;t>50&&t>r&&(r=t,n=e)}),n?(n.scrollBy({top:500,behavior:`smooth`}),e++):t.scrollHeight-t.clientHeight>50&&(t.scrollBy({top:500,behavior:`smooth`}),e++)}),{success:!0,scrolled_elements:e}}case`click_all_comments`:{let t=!1,n=e(`//span[contains(., 'كل التعليقات')]`),r=e(`//span[contains(., 'All comments')]`),i=e(`//span[contains(., 'All Comments')]`);for(let e of[...n,...r,...i]){e.click(),t=!0;break}if(!t){let n=e(`//span[contains(., 'الأكثر ملاءمة')]`),r=e(`//span[contains(., 'Most relevant')]`),i=e(`//span[contains(., 'Most Relevant')]`);for(let e of[...n,...r,...i]){e.click(),t=!0;break}}if(t){await new Promise(e=>setTimeout(e,500));let t=e(`//span[contains(., 'كل التعليقات')]`),n=e(`//span[contains(., 'All comments')]`),r=e(`//span[contains(., 'All Comments')]`),i=e(`//span[contains(., 'الأقدم')]`),a=e(`//span[contains(., 'Oldest')]`);for(let e of[...t,...n,...r,...i,...a]){e.click();break}}let a=e(`//span[contains(., 'عرض المزيد من التعليقات')]`),o=e(`//span[contains(., 'View more comments')]`),s=e(`//span[contains(., 'View all')]`);for(let e of[...a,...o,...s]){e.click();break}return window.scrollBy(0,500),{success:!0,clicked:t}}case`fix_link_navigate`:return t.url?(window.location.href=t.url,{success:!0,navigating:!0}):{success:!1,error:`No URL provided`};default:throw Error(`Unknown action ${t.action}`)}}};if(window.top!==window.self)throw Error(`__IFRAME_SKIP__`);console.log(`Musoftware Content Script Initialized`);function n(){try{return!!(chrome.runtime&&chrome.runtime.id)}catch{return!1}}function r(e,t){if(!n())return;let r={event:e,payload:t,timestamp:Date.now()};try{chrome.runtime.sendMessage({type:`EXTENSION_EVENT`,payload:r},e=>{chrome.runtime.lastError&&console.warn(`[Content] Failed to stream event:`,chrome.runtime.lastError.message)})}catch{}}var i=0;function a(){if(document.getElementById(`__musoftware_extractor_card`)||document.getElementById(`__musoftware_extraction_page`))return;let e=document.createElement(`div`);e.id=`__musoftware_extractor_card`,e.style.cssText=`
    position: fixed;
    top: 24px;
    right: 24px;
    width: 280px;
    background: rgba(15, 23, 42, 0.95);
    border: 1px solid rgba(255, 255, 255, 0.15);
    border-radius: 12px;
    box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.5), 0 8px 10px -6px rgba(0, 0, 0, 0.5);
    color: #f8fafc;
    font-family: 'Outfit', 'Inter', system-ui, -apple-system, BlinkMacSystemFont, sans-serif;
    padding: 16px;
    z-index: 2147483647;
    user-select: none;
    box-sizing: border-box;
  `,g(),_(),e.innerHTML=`
    <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 12px; border-bottom: 1px solid rgba(255,255,255,0.08); padding-bottom: 8px;">
      <div style="display: flex; align-items: center; gap: 8px;">
        <div id="__musoftware_status_dot" style="width: 8px; height: 8px; background: #10b981; border-radius: 50%; box-shadow: 0 0 8px #10b981; animation: __musoftware_pulse 1.5s infinite ease-in-out;"></div>
        <span style="font-weight: 600; font-size: 13px; letter-spacing: 0.5px;">MU EXTRACTOR</span>
      </div>
      <div style="font-size: 10px; background: rgba(56, 189, 248, 0.15); color: #38bdf8; padding: 2px 6px; border-radius: 9999px; font-weight: 500;">Active</div>
    </div>
    
    <div style="margin-bottom: 16px; display: flex; align-items: baseline; justify-content: space-between;">
      <div style="font-size: 12px; color: #94a3b8;">Extracted Items:</div>
      <div id="__musoftware_count_val" style="font-size: 28px; font-weight: 700; color: #38bdf8; font-variant-numeric: tabular-nums;">${i}</div>
    </div>

    <div style="display: flex; flex-direction: column; gap: 8px;">
      <button id="__musoftware_stop_btn" style="
        width: 100%;
        background: #ef4444;
        border: none;
        border-radius: 8px;
        color: white;
        padding: 8px;
        font-size: 12px;
        font-weight: 600;
        cursor: pointer;
        transition: background 0.2s ease-in-out;
      ">Stop Extraction</button>
      <div style="font-size: 10px; color: #64748b; text-align: center; line-height: 1.3;">
        Switching tabs is safe, extraction continues.
      </div>
    </div>
  `,document.body.appendChild(e),document.getElementById(`__musoftware_stop_btn`)?.addEventListener(`click`,()=>{if(n()){try{let e=window.location.hostname.includes(`facebook.com`);chrome.runtime.sendMessage({type:`EXTENSION_EVENT`,payload:{event:e?`facebook.extraction.stop_requested`:`olx.extraction.stop_requested`,payload:{stop:!0},timestamp:Date.now()}})}catch{}s()}})}function o(e){let t=document.getElementById(`__musoftware_count_val`);t&&(t.textContent=String(e))}function s(){let e=document.getElementById(`__musoftware_status_dot`);e&&(e.style.background=`#ef4444`,e.style.boxShadow=`0 0 8px #ef4444`,e.style.animation=`none`);let t=document.getElementById(`__musoftware_stop_btn`);t&&(t.disabled=!0,t.style.background=`#475569`,t.textContent=`Stopped`)}function c(e){let t=document.getElementById(`__musoftware_extractor_card`);t&&t.remove();let n=document.getElementById(`__musoftware_extraction_page`);n&&n.remove();let i=e.type||`members`,a=e.limit||0,o=e.darkMode!==!1,s=e.platformName||`Platform`,c=e.title,l={members:{gradient:o?`linear-gradient(135deg, #121212 0%, #2d2d2d 100%)`:`linear-gradient(135deg, #1e3c72 0%, #2a5298 100%)`,accent:`#007aff`,title:`MU Profile Extractor`},comments:{gradient:o?`linear-gradient(135deg, #121212 0%, #2d2d2d 100%)`:`linear-gradient(135deg, #FF6B6B 0%, #a02020 100%)`,accent:`#FF6B6B`,title:`MU Comment Extractor`},likes:{gradient:o?`linear-gradient(135deg, #121212 0%, #2d2d2d 100%)`:`linear-gradient(135deg, #11998e 0%, #38ef7d 100%)`,accent:`#20bf6b`,title:`MU Reaction Extractor`},followers:{gradient:o?`linear-gradient(135deg, #0f172a 0%, #1e293b 100%)`:`linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%)`,accent:`#3b82f6`,title:`MU Followers Extractor`},following:{gradient:o?`linear-gradient(135deg, #1e1b4b 0%, #312e81 100%)`:`linear-gradient(135deg, #6366f1 0%, #4338ca 100%)`,accent:`#6366f1`,title:`MU Following Extractor`},olx:{gradient:o?`linear-gradient(135deg, #071719 0%, #153235 100%)`:`linear-gradient(135deg, #002f34 0%, #004d56 100%)`,accent:`#002f34`,title:`MU OLX B2C Finder`},dubizzle:{gradient:o?`linear-gradient(135deg, #220a0a 0%, #441414 100%)`:`linear-gradient(135deg, #ff0000 0%, #880000 100%)`,accent:`#ff0000`,title:`MU Dubizzle B2C Finder`}},f=l[i]||l.members,p=c||f.title,m=o?`rgba(30, 30, 30, 0.95)`:`rgba(255, 255, 255, 0.95)`,h=o?`#e0e0e0`:`#333`,_=o?`#a0a0a0`:`#666`,v=o?`#444`:`#f0f0f0`,y=e=>{let t=e;t.id!==`__musoftware_extraction_page`&&t.tagName!==`SCRIPT`&&t.tagName!==`STYLE`&&!t.id.startsWith(`__mu`)&&(t.style.opacity=`0.001`,t.style.pointerEvents=`none`)};document.querySelectorAll(`html body > *`).forEach(y);let b=new MutationObserver(e=>{for(let t of e)t.type===`childList`&&t.addedNodes.forEach(e=>{e.nodeType===Node.ELEMENT_NODE&&y(e)})});b.observe(document.body,{childList:!0}),window.__mu_dom_observer=b,g();let x=document.createElement(`div`);x.id=`__musoftware_extraction_page`,x.style.cssText=`
    position: fixed;
    top: 0; left: 0;
    width: 100vw; height: 100vh;
    background: ${f.gradient};
    display: flex;
    justify-content: center;
    align-items: center;
    z-index: 2147483647;
    font-family: 'Outfit', 'Segoe UI', system-ui, -apple-system, sans-serif;
  `;let S=a>0?`<div style="font-size: 20px; color: ${_}; margin-top: 5px;">/ ${a} (<span id="__mu_progress_pct">0%</span>)</div>`:``;x.innerHTML=`
    <div style="
      background: ${m};
      padding: 40px;
      border-radius: 20px;
      box-shadow: 0 10px 30px rgba(0,0,0,0.2);
      text-align: center;
      width: 420px;
      max-width: 90%;
      backdrop-filter: blur(10px);
    ">
      <h2 style="
        margin: 0 0 20px 0;
        color: ${h};
        font-size: 24px;
        font-weight: 600;
        border-bottom: 2px solid ${v};
        padding-bottom: 15px;
      ">
        <span style="font-size: 28px; vertical-align: middle; margin-right: 8px;">⚡</span>
        ${p}
      </h2>
      
      <div style="margin-bottom: 30px;">
        <div style="font-size: 14px; color: ${_}; font-weight: 500; text-transform: uppercase; letter-spacing: 1px;">Extracted</div>
        <div style="margin-top: 10px; display: flex; justify-content: center; align-items: baseline; gap: 5px;">
          <span id="__mu_extracted_count" style="font-size: 64px; font-weight: 800; color: ${f.accent}; line-height: 1;">0</span>
          ${S}
        </div>
      </div>

      <div style="display: flex; flex-direction: column; gap: 8px;">
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 8px;">
          <button id="__mu_export_csv" style="
            background: ${f.accent}; 
            color: white;
            border: none;
            padding: 12px;
            border-radius: 10px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: transform 0.1s, background 0.2s;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
          ">Export CSV</button>
          <button id="__mu_export_txt" style="
            background: #6c757d; 
            color: white;
            border: none;
            padding: 12px;
            border-radius: 10px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: transform 0.1s, background 0.2s;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
          ">Export TXT</button>
        </div>
        <button id="__mu_export_ids" style="
          background: #28a745; 
          color: white;
          border: none;
          padding: 12px;
          border-radius: 10px;
          font-size: 14px;
          font-weight: 600;
          cursor: pointer;
          transition: transform 0.1s, background 0.2s;
          width: 100%;
          box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        ">Export IDs Only</button>
        <button id="__mu_stop_extraction" style="
          background: #ef4444; 
          color: white;
          border: none;
          padding: 12px;
          border-radius: 10px;
          font-size: 14px;
          font-weight: 600;
          cursor: pointer;
          transition: transform 0.1s, background 0.2s;
          width: 100%;
          box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        ">Stop Extraction</button>
        <button id="__mu_close_ui" style="
          background: transparent; 
          color: ${_};
          border: 1px solid ${v};
          padding: 10px;
          border-radius: 10px;
          font-size: 13px;
          font-weight: 500;
          cursor: pointer;
          transition: background 0.2s;
          margin-top: 4px;
        ">Close UI & Return to ${s}</button>
      </div>
      
      <div style="margin-top: 20px; font-size: 13px; color: #888;">
        <div style="display: flex; align-items: center; justify-content: center; gap: 6px;">
          <div style="width: 8px; height: 8px; background: #22c55e; border-radius: 50%; box-shadow: 0 0 10px #22c55e;"></div>
          <span>Connected to Musoftware Agent</span>
        </div>
      </div>
    </div>
  `,document.body.appendChild(x),[`__mu_export_csv`,`__mu_export_txt`,`__mu_export_ids`,`__mu_stop_extraction`,`__mu_close_ui`].forEach(e=>{let t=document.getElementById(e);t&&(t.addEventListener(`mouseenter`,()=>t.style.transform=e===`__mu_close_ui`?`none`:`translateY(-2px)`),t.addEventListener(`mouseleave`,()=>t.style.transform=`translateY(0)`))});let C=()=>window.location.hostname.includes(`facebook.com`)?`facebook.extraction.export_requested`:`olx.extraction.export_requested`,w=()=>window.location.hostname.includes(`facebook.com`)?`facebook.extraction.stop_requested`:`olx.extraction.stop_requested`;document.getElementById(`__mu_export_csv`)?.addEventListener(`click`,()=>{r(C(),{format:`csv`})}),document.getElementById(`__mu_export_txt`)?.addEventListener(`click`,()=>{r(C(),{format:`txt`})}),document.getElementById(`__mu_export_ids`)?.addEventListener(`click`,()=>{r(C(),{format:`ids`})}),document.getElementById(`__mu_stop_extraction`)?.addEventListener(`click`,()=>{r(w(),{stop:!0}),u()}),document.getElementById(`__mu_close_ui`)?.addEventListener(`click`,()=>{r(`facebook.extraction.stop_requested`,{stop:!0}),d()})}function l(e,t){let n=document.getElementById(`__mu_extracted_count`);if(n&&(n.textContent=String(e)),t&&t>0){let n=document.getElementById(`__mu_progress_pct`);n&&(n.textContent=Math.floor(e/t*100)+`%`)}o(e)}function u(){let e=document.getElementById(`__mu_stop_extraction`);e&&(e.disabled=!0,e.style.background=`#475569`,e.textContent=`Stopped`),s()}function d(){let e=document.getElementById(`__musoftware_extraction_page`);e&&e.remove(),document.querySelectorAll(`html body > *`).forEach(e=>{let t=e;t.id!==`__musoftware_extraction_page`&&t.tagName!==`SCRIPT`&&t.tagName!==`STYLE`&&!t.id.startsWith(`__mu`)&&(t.style.opacity=``,t.style.pointerEvents=``)}),window.__mu_dom_observer&&(window.__mu_dom_observer.disconnect(),delete window.__mu_dom_observer),s()}function f(e){let{format:t,data:n,filename:r}=e,i=``,a=`text/plain`,o=`txt`;t===`csv`?(a=`text/csv`,o=`csv`,i=`﻿`,n.headers&&(i+=n.headers.join(`,`)+`\r
`),n.rows&&n.rows.forEach(e=>{Array.isArray(e)?i+=e.map(e=>`"`+String(e||``).replace(/"/g,`""`)+`"`).join(`,`)+`\r
`:i+=String(e)+`\r
`})):(t===`txt`||t===`ids`)&&Array.isArray(n.rows)&&(i=n.rows.map(e=>Array.isArray(e)?e.join(` : `):String(e)).join(`\r
`));let s=new Blob([i],{type:`${a};charset=utf-8`}),c=URL.createObjectURL(s),l=document.createElement(`a`);l.href=c,l.download=r||`export_${Date.now()}.${o}`,document.body.appendChild(l),l.click(),l.remove(),URL.revokeObjectURL(c)}var p=null;function m(){if(p)return;let e=window.AudioContext||window.webkitAudioContext;e&&(p=new e),window.removeEventListener(`click`,m),window.removeEventListener(`keydown`,m)}window.addEventListener(`click`,m,{once:!0}),window.addEventListener(`keydown`,m,{once:!0});function h(){try{if(!p)return;let e=()=>{let e=p.currentTime,t=p.createOscillator(),n=p.createGain();t.connect(n),n.connect(p.destination),t.type=`sine`,t.frequency.setValueAtTime(523.25,e),t.frequency.exponentialRampToValueAtTime(1046.5,e+.1),n.gain.setValueAtTime(0,e),n.gain.linearRampToValueAtTime(.3,e+.05),n.gain.exponentialRampToValueAtTime(.001,e+1.5),t.start(e),t.stop(e+1.5)};p.state===`suspended`?p.resume().then(e).catch(()=>{}):e()}catch{}}function g(){if(!document.getElementById(`__musoftware_font`)){let e=document.createElement(`link`);e.id=`__musoftware_font`,e.rel=`stylesheet`,e.href=`https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;600;700;800&display=swap`,document.head.appendChild(e)}}function _(){if(!document.getElementById(`__musoftware_style`)){let e=document.createElement(`style`);e.id=`__musoftware_style`,e.textContent=`
      @keyframes __musoftware_pulse {
        0% { transform: scale(0.95); opacity: 0.8; }
        50% { transform: scale(1.1); opacity: 1; }
        100% { transform: scale(0.95); opacity: 0.8; }
      }
      #__musoftware_stop_btn:hover {
        background: #dc2626 !important;
      }
    `,document.head.appendChild(e)}}chrome.runtime.onMessage.addListener((e,i,d)=>{if(n()){if(e.type===`RUNTIME_COMMAND`){let n=e.payload;console.log(`[Content] Received command to execute:`,n);let i=window.location.hostname.includes(`facebook.com`),p=window.location.hostname.includes(`olx.`)||window.location.hostname.includes(`dubizzle.`),m=window.location.hostname.includes(`opensooq.`);if(i||p||m){let e=n;if(e.action===`show_extraction_page`)return c(e),d({success:!0}),!0;if(e.action===`update_extraction_count`)return l(e.count,e.limit),d({success:!0}),!0;if(e.action===`stop_extraction_page`)return u(),d({success:!0}),!0;if(e.action===`export_data`)return f(e),d({success:!0}),!0;if(e.action===`play_completion_sound`)return h(),d({success:!0}),!0;if(e.action===`stop_card`)return s(),d({success:!0}),!0;if(e.action===`update_card`)return a(),o(e.count),d({success:!0}),!0;if(e.action===`scroll_page`){window.scrollBy({top:500,left:0,behavior:`smooth`});try{document.documentElement.scrollBy({top:500,left:0,behavior:`smooth`}),document.body.scrollBy({top:500,left:0,behavior:`smooth`})}catch{}try{document.querySelectorAll(`div`).forEach(e=>{e.scrollHeight>e.clientHeight&&e.clientHeight>200&&e.scrollBy({top:500,left:0,behavior:`smooth`})})}catch{}try{document.querySelectorAll(`[role="dialog"]`).forEach(e=>{e.querySelectorAll(`div`).forEach(e=>{e.scrollHeight>e.clientHeight&&e.clientHeight>0&&e.scrollBy({top:500,left:0,behavior:`smooth`})}),e.scrollHeight>e.clientHeight&&e.scrollBy({top:500,left:0,behavior:`smooth`})})}catch{}return d({response_to:n.id,status:`success`,data:{scrolled:!0}}),!0}if(e.action===`get_html`)return d({response_to:n.id,status:`success`,data:{html:document.documentElement.outerHTML}}),!0;if(e.action===`click_element`){try{let t=e.selector||``,r=document.querySelector(t);if(!r)return d({response_to:n.id,status:`success`,data:{clicked:!1,reason:`Element not found: `+t}}),!0;r.scrollIntoView({behavior:`smooth`,block:`center`}),setTimeout(()=>{r.click(),d({response_to:n.id,status:`success`,data:{clicked:!0}})},400)}catch(e){d({response_to:n.id,status:`error`,error:e.message})}return!0}if(e.action===`extract_opensooq_search_urls`){try{let e=[],t=new Set,r=document.querySelectorAll(`a[href]`);for(let n of r){let r=n.getAttribute(`href`)||``,i=n,a=r.match(/\/(\d{6,})(?:\/?|$)/);if(!a)continue;let o=a[1];if(r.includes(`/job-posters/`)||r.includes(`/mid/`)||r.includes(`/find?`)||r.includes(`/search?`)||r.includes(`/login`)||r.includes(`/register`)||r.includes(`/api/`)||r.includes(`.css`)||r.includes(`.js`)||r.includes(`/font`)||r.includes(`/user/`)||r.includes(`/member/`)||r.includes(`/profile/`)||i.closest(`nav, header, footer, [class*="footer"], [class*="header"], [class*="sidebar"], [class*="recommend"], [class*="Recommend"], [class*="suggest"], [class*="Suggest"], [class*="related"], [class*="Related"], [class*="sponsored"], [class*="Sponsored"]`))continue;let s=i.innerText?.trim()||``;if(s.length<3)continue;let c=r.startsWith(`http`)?r:`https://${window.location.hostname}${r}`;t.has(o)||(t.add(o),e.push({url:c,id:o,title:s.substring(0,120)}))}d({response_to:n.id,status:`success`,data:{urls:e,count:e.length}})}catch(e){d({response_to:n.id,status:`error`,error:e.message})}return!0}if(e.action===`extract_opensooq_detail`){let e={url:window.location.href,title:``,phone:``,email:``,name:``,price:``,region:``,category:``,debug:{}},t=e=>new Promise(t=>setTimeout(t,e)),r=(e,t=2,n=150)=>{for(let r of e)try{let e=document.querySelector(r);if(e){let r=e.innerText?.trim();if(r&&r.length>=t&&r.length<=n)return r}}catch{}return``},i=(e,t)=>{try{return document.querySelector(e)?.getAttribute(t)?.trim()||``}catch{return``}},a=e=>{e.target.closest(`a[href^="tel:"]`)&&(e.preventDefault(),e.stopPropagation())};return(async()=>{try{document.addEventListener(`click`,a,!0);let o=()=>{let e=document.querySelectorAll(`a[href^="tel:"]`);for(let t of e){let e=(t.getAttribute(`href`)||``).replace(`tel:`,``).replace(/[^\d\+]/g,``);if(e.length>=10&&!e.includes(`XX`)&&!e.includes(`xx`))return e}return``},s=()=>{let e=document.body.innerText||``;for(let t of[/(?:\+?2)?01[0125]\d{8}/,/(?:\+?962|07)\d{8,9}/,/(?:\+?966|05)\d{8}/,/(?:\+?971|05)\d{8}/,/(?:\+?965)\d{8}/,/(?:\+?973)\d{8}/,/(?:\+?968)\d{8}/,/(?:\+?974)\d{8}/,/(?:\+?964)\d{10}/,/(?:\+?961)\d{7,8}/,/(?:\+?212)\d{9}/]){let n=e.match(t);if(n)return n[0]}return``},c=()=>{for(let t of[`a[href^="tel:"]`,`button[class*="phone" i]`,`[class*="callButton"]`,`[class*="call-button"]`,`[class*="phoneButton"]`,`[class*="phone-button"]`,`[class*="showPhone"]`,`[class*="show-phone"]`,`[class*="revealPhone"]`,`[class*="contactAction"]`,`[data-action*="phone"]`,`[data-action*="call"]`,`[class*="callNow"]`,`[class*="call-now"]`])try{let n=document.querySelector(t);if(n)return n.scrollIntoView({behavior:`instant`,block:`center`}),n.click(),e.debug.phoneClickSel=t,!0}catch{}let t=document.querySelectorAll(`a, button, [role="button"], [onclick], div[class*="phone" i], span[class*="phone" i]`);for(let n of t){let t=n.innerText||``,r=n.getAttribute(`class`)||``;if(t.includes(`اظهر`)||t.includes(`هاتف`)||t.includes(`اتصل`)||t.includes(`Show`)||t.includes(`Call`)||t.includes(`Phone`)||t.match(/0\d{2}\s*\d{3,}/)||r.match(/phone|call|contact/i))return n.scrollIntoView({behavior:`instant`,block:`center`}),n.click(),e.debug.phoneClickText=t.substring(0,40),!0}return!1};if(e.phone=o(),e.phone&&(e.debug.phoneTry=1),e.phone||c()&&(await t(2e3),e.phone=o(),e.phone&&(e.debug.phoneTry=2)),e.phone||c()&&(await t(2e3),e.phone=o(),e.phone&&(e.debug.phoneTry=3)),e.phone||(e.phone=s(),e.phone&&(e.debug.phoneTry=4)),!e.phone){let t=document.querySelectorAll(`span, a, div, p, strong, b`);for(let n of t){let t=n.innerText?.trim()||``;if(t.match(/^[\d\s\+\-\(\)]{10,15}$/)&&t.replace(/\D/g,``).length>=10){let n=t.replace(/\D/g,``);if(!n.includes(`XX`)&&!n.includes(`xx`)){e.phone=n,e.debug.phoneTry=5;break}}}}document.removeEventListener(`click`,a,!0);try{let t=document.querySelector(`h1`);if(t){let n=t.innerText?.trim();n&&n.length>=3&&(e.title=n,e.debug.titleTry=1)}}catch{}if(!e.title)try{let t=document.title?.replace(/\s*[\|\-]\s*السوق المفتوح.*/g,``)?.replace(/\s*[\|\-]\s*OpenSooq.*/gi,``)?.replace(/\s*-\s*\(\d+\)/g,``)?.trim();t&&t.length>=3&&(e.title=t,e.debug.titleTry=2)}catch{}if(!e.title){let t=i(`meta[property="og:title"]`,`content`);t.length>=3&&(e.title=t,e.debug.titleTry=3)}if(e.name=r([`[class*="memberName" i]`,`[class*="member-name" i]`,`[class*="userName" i]`,`[class*="ownerName" i]`,`[class*="owner-name" i]`,`[class*="advertiser" i]`,`[class*="poster" i]`,`[class*="seller" i]`,`[class*="profileName" i]`,`[class*="profile-name" i]`,`[class*="postOwner" i]`,`[class*="post-owner" i]`],2,80),e.name&&(e.debug.nameTry=1),!e.name){let t=document.querySelectorAll(`img[class*="avatar" i], img[class*="profile" i], img[class*="member" i], img[class*="user" i]`);for(let n of t){let t=n.parentElement;for(let n=0;n<3&&t;n++){let n=t.querySelectorAll(`a, span, h3, h4, h5, strong, p`);for(let t of n){let n=t.innerText?.trim();if(n&&n.length>2&&n.length<60&&!n.includes(`http`)&&!n.match(/^\d+$/)){e.name=n,e.debug.nameTry=2;break}}if(e.name)break;t=t.parentElement}if(e.name)break}}if(!e.name){let t=document.querySelectorAll(`a[href*="/member/"], a[href*="/user/"], a[href*="/profile/"]`);for(let n of t){let t=n.innerText?.trim();if(t&&t.length>2&&t.length<60){e.name=t,e.debug.nameTry=3;break}}}try{let t=document.querySelector(`a[href^="mailto:"]`);t&&(e.email=(t.getAttribute(`href`)||``).replace(`mailto:`,``).split(`?`)[0].trim(),e.email&&(e.debug.emailTry=1))}catch{}if(!e.email){let t=(document.body.innerText||``).match(/[a-zA-Z0-9._%+\-]+@[a-zA-Z0-9.\-]+\.[a-zA-Z]{2,}/);t&&(e.email=t[0],e.debug.emailTry=2)}if(e.price=r([`[class*="price" i]`,`[class*="Price"]`,`[class*="amount" i]`,`[class*="cost" i]`,`[data-price]`],1,50),e.price&&(e.debug.priceTry=1),!e.price){let t=(document.body.innerText||``).match(/([\d,\.]+)\s*(جنيه|دينار|ريال|درهم|EGP|JOD|SAR|AED|KWD|BHD|OMR|QAR|USD|ج\.م)/);t&&(e.price=t[0],e.debug.priceTry=2)}if(!e.price){let t=i(`meta[property="product:price:amount"]`,`content`)||i(`meta[property="og:price:amount"]`,`content`);t&&(e.price=`${t} ${i(`meta[property="product:price:currency"]`,`content`)||``}`.trim(),e.debug.priceTry=3)}if(e.region=r([`[class*="location" i]`,`[class*="Location"]`,`[class*="city" i]`,`[class*="area" i]`,`[class*="address" i]`,`[class*="postLocation" i]`,`[class*="post-location"]`,`[class*="region" i]`,`[class*="Region"]`,`[class*="geo" i]`],2,100),e.region&&(e.debug.regionTry=1),!e.region){let t=document.querySelectorAll(`[class*="breadcrumb" i] a, [class*="breadcrumb" i] span, nav a`),n=[];t.forEach(e=>{let t=e.innerText?.trim();t&&t.length>1&&t.length<60&&!t.includes(`الرئيسية`)&&!t.includes(`Home`)&&!t.includes(`OpenSooq`)&&n.push(t)}),n.length>=2&&(e.region=n[1],e.debug.regionTry=2)}if(!e.region){let t=i(`meta[property="og:locality"]`,`content`)||i(`meta[property="og:region"]`,`content`)||i(`meta[name="geo.region"]`,`content`)||i(`meta[name="geo.placename"]`,`content`);t&&(e.region=t,e.debug.regionTry=3)}try{let t=document.querySelectorAll(`[class*="breadcrumb" i] a`),n=[];t.forEach(e=>{let t=e.innerText?.trim();t&&t.length>1&&t.length<80&&!t.includes(`الرئيسية`)&&!t.includes(`Home`)&&n.push(t)}),n.length>0&&(e.category=n[n.length-1],e.debug.categoryTry=1)}catch{}if(!e.category){let t=i(`meta[property="article:section"]`,`content`)||i(`meta[property="og:type"]`,`content`);t&&t!==`website`&&(e.category=t,e.debug.categoryTry=2)}let l=[];document.querySelectorAll(`[class*="phone" i], [class*="call" i], [class*="contact" i], [class*="mobile" i], a[href^="tel:"]`).forEach(e=>{let t=e.tagName,n=(e.getAttribute(`class`)||``).substring(0,60),r=e.innerText?.substring(0,40)||``,i=e.getAttribute(`href`)||``;l.push(`<${t} class="${n}" href="${i}">${r}`)}),e.debug.phoneElements=l,d({response_to:n.id,status:`success`,data:e})}catch(e){try{document.removeEventListener(`click`,a,!0)}catch{}d({response_to:n.id,status:`error`,error:e.message})}})(),!0}if(e.action===`extract_opensooq_batch`){let t=e.urls||[],r=(e,t)=>new Promise((n,r)=>{let i=document.createElement(`iframe`);i.style.width=`10px`,i.style.height=`10px`,i.style.position=`fixed`,i.style.bottom=`-100px`,i.style.opacity=`0`;let a=!1,o=setTimeout(()=>{a||(document.body.contains(i)&&document.body.removeChild(i),r(Error(`Iframe load timeout`)))},15e3);i.onload=async()=>{a=!0,clearTimeout(o);try{let r=i.contentDocument||i.contentWindow?.document;if(!r)throw Error(`Cannot access iframe document`);let a={url:e,title:``,phone:``,email:``,name:``,price:``,region:``,category:``,debug:{}},o=(e,t=2,n=150)=>{for(let i of e)try{let e=r.querySelector(i);if(e){let r=e.innerText?.trim();if(r&&r.length>=t&&r.length<=n)return r}}catch{}return``},s=(e,t)=>{try{return r.querySelector(e)?.getAttribute(t)?.trim()||``}catch{return``}},c=e=>{e.target.closest(`a[href^="tel:"]`)&&(e.preventDefault(),e.stopPropagation())};r.addEventListener(`click`,c,!0);let l=r.querySelector(`[class*="phone" i], [class*="call" i], [class*="contact" i]`);if(l||=r.querySelector(`a[href^="tel:"]`),l){a.debug.phoneBtnFound=!0;try{l.click()}catch{}await t(1500)}try{let e=r.querySelectorAll(`[class*="phone" i], [class*="call" i], [class*="contact" i], [class*="mobile" i], a[href^="tel:"]`);for(let t of e){let e=t.innerText?.trim();if(e&&/[\\d\\+]{9,}/.test(e.replace(/\\s|-/g,``))){a.phone=e.replace(/[^\\d\\+]/g,``),a.debug.phoneTry=1;break}if(t.tagName===`A`){let e=t.getAttribute(`href`)||``;if(e.startsWith(`tel:`)&&e.length>8){a.phone=e.replace(`tel:`,``).replace(/[^\\d\\+]/g,``),a.debug.phoneTry=`1_href`;break}}}}catch{}if(!a.phone){let e=(r.body?.innerText||``).match(/(?:010|011|012|015|002010|002011|002012|002015|\\+2010|\\+2011|\\+2012|\\+2015)[\\s-]?\\d{3}[\\s-]?\\d{4}/g);e&&e.length>0&&(a.phone=e[0].replace(/[^\\d\\+]/g,``),a.debug.phoneTry=2)}if(a.title=o([`h1`,`title`,`.post-title`,`[class*="title" i] h1`],3,150)||s(`meta[property="og:title"]`,`content`),a.price=o([`[class*="price" i]`,`[class*="Price" i]`,`.amount`,`.value`],2,30)||s(`meta[property="og:price:amount"]`,`content`),a.name=o([`[class*="author" i]`,`[class*="seller" i]`,`[class*="user" i]`,`[class*="member" i]`,`[href*="/member/"]`],2,50),!a.name){let e=r.querySelector(`a[href*="/member/"]`);e&&(a.name=e.innerText?.trim())}a.region=o([`[class*="location" i]`,`[class*="address" i]`,`[class*="area" i]`,`[class*="city" i]`],2,100)||s(`meta[name="geo.region"]`,`content`)||s(`meta[name="geo.placename"]`,`content`);try{let e=r.querySelectorAll(`[class*="breadcrumb" i] a`),t=[];e.forEach(e=>{let n=e.innerText?.trim();n&&n.length>1&&n.length<80&&!n.includes(`الرئيسية`)&&!n.includes(`Home`)&&t.push(n)}),t.length>0&&(a.category=t[t.length-1])}catch{}try{r.removeEventListener(`click`,c,!0)}catch{}document.body.contains(i)&&document.body.removeChild(i),n(a)}catch(e){document.body.contains(i)&&document.body.removeChild(i),r(e)}},i.src=e,document.body.appendChild(i)});return(async()=>{let e=[],i=e=>new Promise(t=>setTimeout(t,e));for(let n=0;n<t.length;n++){let a=t[n];try{let t=await r(a,i);(t.title.includes(`502 Bad Gateway`)||t.title.includes(`Just a moment`)||t.title.includes(`Cloudflare`))&&(t.error=`Rate Limited (502 Bad Gateway)`,t.title=`502 Bad Gateway`,await i(5e3)),e.push(t)}catch(t){e.push({url:a,error:t.message,title:``,phone:``,name:``,price:``,region:``,category:``})}await i(1500+Math.random()*2e3)}d({response_to:n.id,status:`success`,data:{results:e}})})(),!0}if(e.action===`execute_fetch`){let{url:t,options:r}=e;return fetch(t,r||{}).then(async e=>{let t=await e.text(),r=null;try{r=JSON.parse(t)}catch{}d({response_to:n.id,status:`success`,data:{status:e.status,text:t,json:r,url:e.url}})}).catch(e=>{d({response_to:n.id,status:`error`,error:e.message})}),!0}if(i){let i={...n};return i._streamResultFn=(e,t)=>{r(`graphql_fetch_response`,{data:e,requestBody:t})},e.action!==`execute_graphql`&&a(),t.executeCommand(i).then(e=>{d({response_to:n.id,status:`success`,data:e})}).catch(e=>{d({response_to:n.id,status:`error`,error:e.message})}),!0}else return d({response_to:n.id,status:`error`,error:`Action ${e.action} not supported on OLX/Dubizzle`}),!0}else d({response_to:n.id,status:`error`,error:`Unsupported platform`})}return!0}});function v(e){if(n())try{let t=document.createElement(`script`);t.setAttribute(`type`,`text/javascript`),t.setAttribute(`src`,chrome.runtime.getURL(e)),document.documentElement.appendChild(t)}catch{}}window.sessionStorage.getItem(`__musoftware_injected`)||(v(`src/inject.js`),window.sessionStorage.setItem(`__musoftware_injected`,`true`)),window.addEventListener(`message`,e=>{if(e.source===window&&e.data&&e.data.type===`EXTENSION_FETCH_INTERCEPT`){let t=e.data.payload;t&&t.data&&r(`graphql_fetch_response`,t)}});