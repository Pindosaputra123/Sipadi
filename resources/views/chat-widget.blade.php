{{-- Quick Ball / Thin Edge Handle --}}
<div id="chat-edge-handle" onclick="expandFab()" title="Tampilkan BOTANI">
    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin-left: 4px;">
        <path d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5z"/>
    </svg>
</div>

{{-- Floating Chat Button --}}
<div id="chat-fab" class="hidden" onclick="chatToggle()" title="Chat dengan BOTANI">
    <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <path d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5z"/>
    </svg>
</div>

{{-- Chat Window --}}
<div id="chat-window" class="hidden">
    {{-- Header --}}
    <div id="chat-header">
        <div style="display:flex;align-items:center;gap:10px;">
            <div id="chat-bot-avatar">🤖</div>
            <div>
                <div style="color:white;font-weight:700;font-size:14px;line-height:1.2;">BOTANI</div>
                <div style="color:rgba(255,255,255,0.75);font-size:11px;">Asisten AI SIPADI</div>
            </div>
        </div>
        <button onclick="closeChat()" id="chat-close-btn" title="Tutup chat">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2.5" stroke-linecap="round">
                <line x1="18" y1="6" x2="6" y2="18"/>
                <line x1="6" y1="6" x2="18" y2="18"/>
            </svg>
        </button>
    </div>

    {{-- Messages --}}
    <div id="chat-messages">
        <div class="chat-bubble bot">Halo Bro! Saya BOTANI, asisten AI untuk SIPADI. Ada yang bisa saya bantu terkait stok dan harga? 🌾</div>
    </div>

    {{-- Input --}}
    <div id="chat-input-area">
        <input
            id="chat-input"
            type="text"
            placeholder="Tulis pertanyaan..."
            autocomplete="off"
            onkeypress="if(event.key==='Enter')chatSend()"
        />
        <button id="chat-send-btn" onclick="chatSend()" title="Kirim">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="white">
                <path d="M2.01 21L23 12 2.01 3 2 10l15 2-15 2z"/>
            </svg>
        </button>
    </div>
</div>

<style>
/* ── Thin Edge Handle (Quick Ball Collapsed) ── */
#chat-edge-handle {
    position: fixed;
    top: 50%;
    right: 0;
    transform: translateY(-50%);
    width: 38px;
    height: 48px;
    background: linear-gradient(135deg, #16a34a, #15803d);
    border-radius: 24px 0 0 24px;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 10000;
    transition: width 0.2s, background 0.2s;
    box-shadow: -3px 0 12px rgba(0,0,0,0.4);
}
#chat-edge-handle:hover {
    width: 46px;
    background: linear-gradient(135deg, #15803d, #16a34a);
}
#chat-edge-handle.hidden {
    transform: translateY(-50%) translateX(100%);
    opacity: 0;
    pointer-events: none;
}

/* ── FAB Button (Quick Ball Expanded) ── */
#chat-fab {
    position: fixed;
    top: 50%;
    right: 24px;
    transform: translateY(-50%) scale(1);
    width: 56px;
    height: 56px;
    background: linear-gradient(135deg, #16a34a, #15803d);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    box-shadow: 0 4px 16px rgba(22,163,74,0.45);
    z-index: 10000;
    transition: all 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);
    user-select: none;
    opacity: 1;
}
#chat-fab:hover {
    transform: translateY(-50%) scale(1.08);
    box-shadow: 0 6px 24px rgba(22,163,74,0.6);
}
#chat-fab:active { transform: translateY(-50%) scale(0.95); }

#chat-fab.hidden {
    transform: translateY(-50%) scale(0.5) translateX(100px);
    opacity: 0;
    pointer-events: none;
}

/* ── Chat Window ── */
#chat-window {
    position: fixed;
    bottom: 24px;
    right: 24px;
    width: 340px;
    height: 480px;
    max-height: calc(100vh - 48px);
    background: #111827;
    border-radius: 18px;
    box-shadow: 0 12px 40px rgba(0,0,0,0.5), 0 0 0 1px rgba(255,255,255,0.07);
    display: flex;
    flex-direction: column;
    overflow: hidden;
    z-index: 10001;
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
    transform: scale(1) translateY(0);
    opacity: 1;
    transition: all 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);
    transform-origin: bottom right;
}
#chat-window.hidden {
    transform: scale(0.85) translateY(40px);
    opacity: 0;
    pointer-events: none;
}

/* ── Header ── */
#chat-header {
    background: linear-gradient(135deg, #16a34a, #15803d);
    padding: 12px 14px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    flex-shrink: 0;
}
#chat-bot-avatar {
    width: 34px;
    height: 34px;
    background: rgba(255,255,255,0.18);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 17px;
}
#chat-close-btn {
    background: rgba(255,255,255,0.15);
    border: none;
    border-radius: 8px;
    width: 30px;
    height: 30px;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: background 0.15s;
    padding: 0;
}
#chat-close-btn:hover { background: rgba(255,255,255,0.28); }

/* ── Messages ── */
#chat-messages {
    flex: 1;
    overflow-y: auto;
    padding: 14px;
    display: flex;
    flex-direction: column;
    gap: 9px;
    background: #0d1117;
    scroll-behavior: smooth;
}
#chat-messages::-webkit-scrollbar { width: 4px; }
#chat-messages::-webkit-scrollbar-thumb { background: rgba(255,255,255,0.12); border-radius: 4px; }

.chat-bubble {
    padding: 9px 13px;
    border-radius: 14px;
    font-size: 13px;
    max-width: 85%;
    line-height: 1.55;
    white-space: pre-wrap;
    word-break: break-word;
    animation: bubblePop 0.18s ease;
}
@keyframes bubblePop {
    from { opacity: 0; transform: scale(0.92) translateY(4px); }
    to   { opacity: 1; transform: scale(1) translateY(0); }
}
.chat-bubble.bot {
    background: #1f2937;
    color: #e5e7eb;
    align-self: flex-start;
    border-bottom-left-radius: 4px;
}
.chat-bubble.user {
    background: linear-gradient(135deg, #16a34a, #15803d);
    color: white;
    align-self: flex-end;
    border-bottom-right-radius: 4px;
}
.chat-bubble.typing {
    background: #1f2937;
    color: #6b7280;
    align-self: flex-start;
}

/* ── Input Area ── */
#chat-input-area {
    padding: 10px 12px;
    background: #111827;
    border-top: 1px solid rgba(255,255,255,0.07);
    display: flex;
    gap: 8px;
    align-items: center;
    flex-shrink: 0;
}
#chat-input {
    flex: 1;
    background: #1f2937;
    border: 1px solid rgba(255,255,255,0.1);
    border-radius: 20px;
    padding: 9px 14px;
    color: white;
    font-size: 13px;
    outline: none;
    transition: border-color 0.2s;
    font-family: inherit;
}
#chat-input:focus { border-color: rgba(22,163,74,0.6); }
#chat-input::placeholder { color: #6b7280; }
#chat-send-btn {
    background: linear-gradient(135deg, #16a34a, #15803d);
    border: none;
    border-radius: 50%;
    width: 36px;
    height: 36px;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    transition: transform 0.15s, box-shadow 0.15s;
    box-shadow: 0 2px 8px rgba(22,163,74,0.4);
}
#chat-send-btn:hover { transform: scale(1.08); }
#chat-send-btn:active { transform: scale(0.93); }

@media (max-width: 480px) {
    #chat-window {
        width: calc(100vw - 24px);
        right: 12px;
        bottom: 12px;
        height: calc(100vh - 24px);
    }
}
</style>

<script>
(function () {
    const CHAT_ENDPOINT = '{{ route("chatbot.prabowo") }}';
    const CSRF_TOKEN    = '{{ csrf_token() }}';

    const edgeHandle = document.getElementById('chat-edge-handle');
    const fab        = document.getElementById('chat-fab');
    const win        = document.getElementById('chat-window');
    const messages   = document.getElementById('chat-messages');
    const input      = document.getElementById('chat-input');

    // Default state: Only edge handle visible
    // To make sure things align cleanly on load, we enforce the classes.

    window.expandFab = function () {
        // Hide edge handle, show FAB
        edgeHandle.classList.add('hidden');
        fab.classList.remove('hidden');
    };

    window.chatToggle = function () {
        // Hide FAB, show window
        fab.classList.add('hidden');
        win.classList.remove('hidden');
        setTimeout(() => input.focus(), 300);
    };

    window.closeChat = function () {
        // Hide window, show edge handle (reset to initial state)
        win.classList.add('hidden');
        edgeHandle.classList.remove('hidden');
    };

    function addBubble(text, type) {
        const b = document.createElement('div');
        b.className = `chat-bubble ${type}`;
        b.textContent = text;
        messages.appendChild(b);
        messages.scrollTop = messages.scrollHeight;
        return b;
    }

    window.chatSend = async function () {
        const text = input.value.trim();
        if (!text) return;
        addBubble(text, 'user');
        input.value = '';
        const loading = addBubble('Mengetik...', 'typing');

        try {
            const res  = await fetch(CHAT_ENDPOINT, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': CSRF_TOKEN,
                    'Accept': 'application/json',
                },
                body: JSON.stringify({ message: text }),
            });
            const data = await res.json();
            loading.className = 'chat-bubble bot';
            loading.textContent = data.reply || 'Maaf Bro, tidak ada respons. Coba lagi ya.';
        } catch {
            loading.className = 'chat-bubble bot';
            loading.textContent = 'Maaf Bro, gagal menghubungi server.';
        }
        messages.scrollTop = messages.scrollHeight;
    };
})();
</script>