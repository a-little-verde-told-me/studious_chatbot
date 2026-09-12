<div id="leon-widget-root"></div>

<!-- Include marked.js for parsing Markdown output -->
<script src="https://cdn.jsdelivr.net/npm/marked/marked.min.js"></script>

<style>
/* ---------- Leon: floating AI assistant ---------- */
.leon-fab{position:fixed;bottom:24px;right:24px;width:66px;height:66px;border-radius:50%;
  background:radial-gradient(circle at 32% 28%,#FFFDF7,#FCE3B3 70%);
  box-shadow:0 10px 28px rgba(0,0,0,.22);display:flex;align-items:center;justify-content:center;
  cursor:pointer;z-index:950;border:3px solid #fff;transition:transform .15s ease;}
.leon-fab:hover{transform:scale(1.07);}
.leon-fab-dot{position:absolute;top:1px;right:1px;width:15px;height:15px;background:#EA4335;
  border:2.5px solid #fff;border-radius:50%;}
.leon-face{overflow:visible;animation:leonBounce 2.8s ease-in-out infinite;transform-origin:center;}
@keyframes leonBounce{0%,100%{transform:translateY(0) rotate(0deg);}50%{transform:translateY(-4px) rotate(-4deg);}}
.leon-eye{transform-box:fill-box;transform-origin:center;animation:leonBlink 4.6s ease-in-out infinite;}
@keyframes leonBlink{0%,90%,100%{transform:scaleY(1);}93%{transform:scaleY(.1);}96%{transform:scaleY(1);}}

.leon-panel{position:fixed;bottom:104px;right:24px;width:346px;max-width:calc(100vw - 32px);
  height:472px;max-height:calc(100vh - 150px);background:#fff;border-radius:18px;
  box-shadow:0 22px 60px rgba(0,0,0,.28);display:flex;flex-direction:column;overflow:hidden;z-index:960;}
.leon-panel-header{background:#1e40af;padding:14px 16px;
  display:flex;justify-content:space-between;align-items:center;color:#fff;flex-shrink:0;}
.leon-panel-header .name{font-weight:800;font-size:14.5px;}
.leon-panel-header .status{font-size:11px;opacity:.9;display:flex;align-items:center;gap:5px;}
.leon-panel-header .status::before{content:'';width:7px;height:7px;border-radius:50%;background:#4ADE80;display:inline-block;}
.leon-panel-close{background:rgba(255,255,255,.2);border:none;border-radius:50%;width:28px;height:28px;
  display:flex;align-items:center;justify-content:center;color:#fff;cursor:pointer;flex-shrink:0;}
.leon-panel-body{flex:1;overflow-y:auto;padding:16px;background:#FBFCFE;}

.leon-msg-group { display: flex; flex-direction: column; gap: 8px; margin-bottom: 14px; width: 100%; max-width: 100%; }
.leon-msg-group.me { align-items: flex-end; }

.leon-msg{display:flex;gap:8px;width:100%;}
.leon-msg.me{flex-direction:row-reverse;}

/* --- Compact Formatted Bubble & Markdown Styles --- */
.leon-bubble{background:#F1F2F5;padding:10px 14px;border-radius:14px;font-size:13.5px;line-height:1.45;color:#1e293b;word-break:break-word;max-width:82%;height:auto;min-height:fit-content;}
.leon-bubble p{margin:0 0 6px 0;line-height:1.45;}
.leon-bubble p:last-child{margin-bottom:0;}
.leon-bubble ul, .leon-bubble ol{margin:4px 0 6px 0;padding-left:18px;}
.leon-bubble li{margin-bottom:2px;}
.leon-msg.me .leon-bubble{background:#1e40af;color:#fff;}

/* --- Compact FAQ Quick Action Chips --- */
.leon-faq-container {display:flex;flex-wrap:wrap;gap:6px;margin-top:2px;padding-left:38px;max-width:88%;}
.leon-faq-chip {
  background:#ffffff;border:1px solid #1e40af;color:#1e40af;
  padding:6px 12px;border-radius:14px;font-size:12px;font-weight:600;
  cursor:pointer;transition:all 0.15s ease;text-align:center;
  white-space:normal;line-height:1.2;
}
.leon-faq-chip:hover {
  background:#1e40af;color:#ffffff;transform:translateY(-1px);
  box-shadow:0 2px 6px rgba(30, 64, 175, 0.2);
}
.leon-faq-chip.disabled {
  opacity: 0.5; pointer-events: none; cursor: not-allowed;
}

.leon-av{width:30px;height:30px;border-radius:50%;flex-shrink:0;display:flex;align-items:center;justify-content:center;}
.leon-msg.me .leon-av{background:#F4B400;color:#00295a;font-weight:700;font-size:12px;}
.leon-panel-input{display:flex;gap:8px;padding:12px;border-top:1px solid #E4E7ED;flex-shrink:0;background:#fff;}
.leon-panel-input input{flex:1;padding:10px 14px;border:1px solid #E4E7ED;border-radius:9px;font-size:14px;outline:none;}
.leon-panel-input input:disabled{background:#f8fafc;cursor:not-allowed;}
.leon-panel-input button{background:#1e40af;color:#fff;border:none;border-radius:9px;width:38px;
  display:flex;align-items:center;justify-content:center;cursor:pointer;flex-shrink:0;}
.leon-panel-input button:disabled{opacity:0.5;cursor:not-allowed;}
@media(max-width:600px){
  .leon-panel{right:12px;left:12px;width:auto;bottom:96px;}
  .leon-fab{right:16px;bottom:16px;}
}
</style>

<script>
(function(){
  let open = false;
  let isStreaming = false;
  
  let history = [{ 
    role: 'assistant', 
    content: "Roar! I'm Leon, your StudiOUS assistant. Select a topic below or ask me a question!" 
  }];

  const faqList = [
    "How to enroll?",
    "Payment methods",
    "Application requirements",
    "Graduation application"
  ];

  function lionFace(size){
    const r = size/2;
    let mane = '';
    for(let i=0;i<14;i++){
      const angle = (360/14)*i;
      const col = i%2===0 ? '#F4B400' : '#D97706';
      mane += `<polygon points="0,-${r*0.62} -${r*0.1},-${r} ${r*0.1},-${r}" fill="${col}" transform="rotate(${angle})"/>`;
    }
    return `<svg width="${size}" height="${size}" viewBox="${-r} ${-r} ${size} ${size}" class="leon-face">
      <g>
        ${mane}
        <circle r="${r*0.6}" fill="#FCE3B3"/>
        <circle cx="-${r*0.42}" cy="-${r*0.5}" r="${r*0.17}" fill="#F4B400"/>
        <circle cx="${r*0.42}" cy="-${r*0.5}" r="${r*0.17}" fill="#F4B400"/>
        <ellipse cx="0" cy="${r*0.14}" rx="${r*0.32}" ry="${r*0.26}" fill="#FFF3DC"/>
        <circle class="leon-eye" cx="-${r*0.24}" cy="-${r*0.06}" r="${r*0.075}" fill="#2b1b0e"/>
        <circle class="leon-eye" cx="${r*0.24}" cy="-${r*0.06}" r="${r*0.075}" fill="#2b1b0e"/>
        <path d="M-${r*0.02},${r*0.1} l${r*0.04},0 l-${r*0.02},${r*0.03} Z" fill="#8a5a2b"/>
      </g>
    </svg>`;
  }

  function renderContent(content, role) {
      if (!content) return ''; 
      if (role === 'user') return escapeHtml(content);
      if (window.marked) return marked.parse(content.trim());
      return escapeHtml(content);
    }

  function render(){
    const root = document.getElementById('leon-widget-root');
    root.innerHTML = `
      ${open ? panelHtml() : ''}
      <div class="leon-fab" onclick="window.__leonToggle()">${lionFace(42)}</div>
    `;
    if(open){
      const body = document.getElementById('leonBody');
      if(body) body.scrollTop = body.scrollHeight;
    }
  }

  function panelHtml(){
    return `<div class="leon-panel">
      <div class="leon-panel-header">
        <div style="display:flex;align-items:center;gap:10px">
          ${lionFace(36)}
          <div><div class="name">Leon</div><div class="status">Online · StudiOUS Assistant</div></div>
        </div>
        <button class="leon-panel-close" onclick="window.__leonToggle()">&times;</button>
      </div>
      <div class="leon-panel-body" id="leonBody">
        ${history.map((m, index) => `
          <div class="leon-msg-group ${m.role==='user'?'me':''}">
            <div class="leon-msg ${m.role==='user'?'me':''}">
              <div class="leon-av">${m.role==='user' ? 'You' : lionFace(28)}</div>
              <div class="leon-bubble">${renderContent(m.content, m.role)}</div>
            </div>
            ${(index === 0 && m.role === 'assistant') ? `
              <div class="leon-faq-container">
                ${faqList.map(faq => `
                  <button class="leon-faq-chip ${isStreaming ? 'disabled' : ''}" onclick="window.__leonSendFaq('${escapeHtml(faq)}')">
                    ${escapeHtml(faq)}
                  </button>
                `).join('')}
              </div>
            ` : ''}
          </div>`).join('')}
      </div>
      <div class="leon-panel-input">
        <input id="leonInput" placeholder="${isStreaming ? 'Leon is thinking...' : 'Ask Leon something...'}" ${isStreaming ? 'disabled' : ''} onkeydown="if(event.key==='Enter') window.__leonSend()">
        <button ${isStreaming ? 'disabled' : ''} onclick="window.__leonSend()">&#10148;</button>
      </div>
    </div>`;
  }

  function escapeHtml(s){
    const d = document.createElement('div');
    d.textContent = s;
    return d.innerHTML;
  }

  window.__leonToggle = function(){
    open = !open;
    render();
    if(open) setTimeout(() => document.getElementById('leonInput')?.focus(), 50);
  };

  window.__leonSendFaq = function(text) {
    if (isStreaming) return;
    const input = document.getElementById('leonInput');
    if (input) input.value = text;
    window.__leonSend();
  };

  window.__leonSend = async function(){
    if (isStreaming) return;
    
    const input = document.getElementById('leonInput');
    if (!input) return;
    const val = input.value.trim();
    if(!val) return;

    isStreaming = true;
    history.push({ role:'user', content: val });
    history.push({ role:'assistant', content: '…' });
    input.value = '';
    render();

    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';

    try {
      const response = await fetch('/api/chatbot/stream', {
        method: 'POST',
        headers: { 
          'Content-Type': 'application/json', 
          'X-CSRF-TOKEN': csrfToken 
        },
        body: JSON.stringify({
          message: val,
          history: history.slice(0, -2).slice(-10),
        })
      });

      if (response.status === 429) {
        history[history.length - 1] = {
          role: 'assistant',
          content: "You're sending messages too fast. Please wait a moment."
        };
        isStreaming = false;
        render();
        return;
      }

      if (!response.ok) throw new Error('Stream request failed');

      const reader = response.body.getReader();
      const decoder = new TextDecoder();
      let assistantMessage = '';
      let rawBuffer = '';
      
      history[history.length - 1].content = '';

      while (true) {
        const { value, done } = await reader.read();
        if (done) break;
        
        const chunk = decoder.decode(value, { stream: true });
        rawBuffer += chunk;

        // Check if Gemini API returned a quota or error JSON object in the stream
        if (rawBuffer.includes('RESOURCE_EXHAUSTED') || rawBuffer.includes('"error":')) {
          try {
            const errJson = JSON.parse(rawBuffer);
            if (errJson.error) {
              assistantMessage = "⚠️ Gemini API Quota Exceeded (429). Please wait a moment or check your Google AI Studio plan limits.";
              break;
            }
          } catch(e) {
            // Wait for full JSON payload if it's still streaming chunks
          }
        }

        const lines = chunk.split('\n');
        for (const line of lines) {
          if (line.startsWith('data: ')) {
            const jsonStr = line.substring(6).trim();
            if (!jsonStr) continue;
            try {
              const parsed = JSON.parse(jsonStr);
              const parts = parsed.candidates?.[0]?.content?.parts;
              if (parts && Array.isArray(parts)) {
                for (const part of parts) {
                  if (part.text) assistantMessage += part.text;
                }
              }
            } catch (e) {}
          }
        }
        
        if (assistantMessage) {
          history[history.length - 1].content = assistantMessage;
          render();
        }
      }

      if (!assistantMessage) {
        history[history.length - 1].content = "⚠️ Gemini API Quota Exceeded (429). Please try again later.";
        render();
      }

    } catch(err) {
      history[history.length - 1] = {
        role: 'assistant',
        content: "I'm having trouble connecting right now. Please try again, or open a Helpdesk ticket.",
      };
    } finally {
      isStreaming = false;
      render();
    }
  };

  document.addEventListener('DOMContentLoaded', render);
})();
</script>