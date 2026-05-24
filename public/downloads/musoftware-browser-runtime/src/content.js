var e={processNetworkEvent(e){if(!e||!e.data)return null;let{data:t,requestBody:n}=e,r=null,i=null,a=t.data?.node||t.data?.group||t.data;if(a?.new_forum_members||a?.new_members||a?.people_profiles||a?.members)r=`facebook.graphql.group_members`,i=a;else if(a?.comet_ufi_summary_and_actions_from_feedback?.comments||a?.comments)r=`facebook.graphql.comments`,i=a.comet_ufi_summary_and_actions_from_feedback?.comments||a.comments;else if(a?.reactors)r=`facebook.graphql.likes`,i=a.reactors;else return null;return{event:r,payload:i,requestBody:n}},async executeCommand(e){switch(e.action){case`navigate`:return e.target?.url?(window.location.href=e.target.url,{success:!0,navigating:!0}):{success:!1,error:`No target URL provided`};case`execute_graphql`:if(!e.requestBody)return{success:!1,error:`No requestBody provided`};try{return fetch(`/api/graphql/`,{method:`POST`,headers:{"Content-Type":`application/x-www-form-urlencoded`},body:e.requestBody}),{success:!0,requested:!0}}catch(e){return{success:!1,error:e.message}}case`scroll_page`:return window.scrollBy(0,99999),{success:!0,scrolled:!0};case`click_likes_dialog`:let t=document.querySelector(`*[aria-label="تعرف على الأشخاص الذين تفاعلوا مع هذا"] div`),n=document.querySelector(`*[aria-label="See who reacted to this"] div`);return t&&t.click(),n&&n.click(),{success:!0,clicked:!!(t||n)};case`scroll_dialogs`:let r=document.querySelectorAll(`*[role="dialog"] div`),i=0;return r.forEach(e=>{e.scrollBy(0,99999),i++}),{success:!0,dialogs_scrolled:i};default:throw Error(`Unknown action ${e.action}`)}}};console.log(`Musoftware Content Script Initialized`);function t(e,t){let n={event:e,payload:t,timestamp:Date.now()};chrome.runtime.sendMessage({type:`EXTENSION_EVENT`,payload:n},e=>{chrome.runtime.lastError&&console.warn(`[Content] Failed to stream event:`,chrome.runtime.lastError.message)})}var n=0;function r(){if(document.getElementById(`__musoftware_extractor_card`))return;let e=document.createElement(`div`);if(e.id=`__musoftware_extractor_card`,e.style.cssText=`
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
  `,!document.getElementById(`__musoftware_font`)){let e=document.createElement(`link`);e.id=`__musoftware_font`,e.rel=`stylesheet`,e.href=`https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;600;700&display=swap`,document.head.appendChild(e)}if(!document.getElementById(`__musoftware_style`)){let e=document.createElement(`style`);e.id=`__musoftware_style`,e.textContent=`
      @keyframes __musoftware_pulse {
        0% { transform: scale(0.95); opacity: 0.8; }
        50% { transform: scale(1.1); opacity: 1; }
        100% { transform: scale(0.95); opacity: 0.8; }
      }
      #__musoftware_stop_btn:hover {
        background: #dc2626 !important;
      }
    `,document.head.appendChild(e)}e.innerHTML=`
    <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 12px; border-bottom: 1px solid rgba(255,255,255,0.08); padding-bottom: 8px;">
      <div style="display: flex; align-items: center; gap: 8px;">
        <div id="__musoftware_status_dot" style="width: 8px; height: 8px; background: #10b981; border-radius: 50%; box-shadow: 0 0 8px #10b981; animation: __musoftware_pulse 1.5s infinite ease-in-out;"></div>
        <span style="font-weight: 600; font-size: 13px; letter-spacing: 0.5px;">MU EXTRACTOR</span>
      </div>
      <div style="font-size: 10px; background: rgba(56, 189, 248, 0.15); color: #38bdf8; padding: 2px 6px; border-radius: 9999px; font-weight: 500;">Active</div>
    </div>
    
    <div style="margin-bottom: 16px; display: flex; align-items: baseline; justify-content: space-between;">
      <div style="font-size: 12px; color: #94a3b8;">Extracted Items:</div>
      <div id="__musoftware_count_val" style="font-size: 28px; font-weight: 700; color: #38bdf8; font-variant-numeric: tabular-nums;">${n}</div>
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
  `,document.body.appendChild(e),document.getElementById(`__musoftware_stop_btn`)?.addEventListener(`click`,()=>{chrome.runtime.sendMessage({type:`EXTENSION_EVENT`,payload:{event:`facebook.extraction.stop_requested`,payload:{stop:!0},timestamp:Date.now()}}),a()})}function i(e){let t=document.getElementById(`__musoftware_count_val`);t&&(t.textContent=String(e))}function a(){let e=document.getElementById(`__musoftware_status_dot`);e&&(e.style.background=`#ef4444`,e.style.boxShadow=`0 0 8px #ef4444`,e.style.animation=`none`);let t=document.getElementById(`__musoftware_stop_btn`);t&&(t.disabled=!0,t.style.background=`#475569`,t.textContent=`Stopped`)}chrome.runtime.onMessage.addListener((t,n,o)=>{if(t.type===`RUNTIME_COMMAND`){let n=t.payload;if(console.log(`[Content] Received command to execute:`,n),window.location.hostname.includes(`facebook.com`))return r(),n.action===`stop_card`?(a(),o({success:!0}),!0):n.action===`update_card`?(i(n.count),o({success:!0}),!0):(e.executeCommand(n).then(e=>{o({response_to:n.id,status:`success`,data:e})}).catch(e=>{o({response_to:n.id,status:`error`,error:e.message})}),!0);o({response_to:n.id,status:`error`,error:`Unsupported platform`})}return!0});function o(e){let t=document.createElement(`script`);t.setAttribute(`type`,`text/javascript`),t.setAttribute(`src`,chrome.runtime.getURL(e)),document.documentElement.appendChild(t)}window.sessionStorage.getItem(`__musoftware_injected`)||(o(`src/inject.js`),window.sessionStorage.setItem(`__musoftware_injected`,`true`)),window.addEventListener(`message`,e=>{if(e.source===window&&e.data&&e.data.type===`EXTENSION_FETCH_INTERCEPT`){let n=e.data.payload;n&&n.data&&t(`graphql_fetch_response`,n)}});