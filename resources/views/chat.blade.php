<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Chat Pro</h2>
            <button onclick="window.createGroup()" class="bg-indigo-600 text-white px-4 py-2 rounded-lg font-bold shadow-md hover:bg-indigo-700 transition">+ Grup Baru</button>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white shadow-xl sm:rounded-lg flex" style="height: 600px;">
                
                <div class="w-1/3 border-r bg-gray-50 flex flex-col p-4 overflow-y-auto">
                    
                    <h3 class="text-xs font-bold text-gray-400 mb-2 uppercase tracking-widest">Groups</h3>
                    <div id="group-list" class="mb-6 space-y-2">
                        @forelse($groups as $group)
                            <div onclick="window.selectChat('group', {{ $group->id }}, '{{ $group->name }}')" 
                                 id="group-{{ $group->id }}" class="sidebar-item p-3 bg-white border rounded-xl cursor-pointer hover:bg-indigo-50 flex items-center gap-3 relative">
                                <div class="w-10 h-10 rounded-full bg-emerald-100 flex items-center justify-center font-bold text-emerald-700">
                                    #
                                </div>
                                <span class="font-medium text-gray-700">{{ $group->name }}</span>
                            </div>
                        @empty
                            <p class="text-xs italic text-gray-400 pl-2">Belum ada grup diikuti.</p>
                        @endforelse
                    </div>

                    <h3 class="text-xs font-bold text-gray-400 mb-2 uppercase tracking-widest">Direct Messages</h3>
                    <div id="user-list" class="space-y-2">
                        @foreach($users as $user)
                            <div onclick="window.selectChat('user', {{ $user->id }}, '{{ $user->name }}')" 
                                 id="user-{{ $user->id }}" class="sidebar-item p-3 bg-white border rounded-xl cursor-pointer hover:bg-indigo-50 flex items-center gap-3 relative">
                                <div class="w-10 h-10 rounded-full bg-indigo-100 flex items-center justify-center font-bold text-indigo-700">
                                    {{ substr($user->name, 0, 1) }}
                                </div>
                                <span class="font-medium text-gray-700">{{ $user->name }}</span>
                                <div id="status-{{ $user->id }}" class="hidden absolute left-10 bottom-3 w-3 h-3 bg-green-500 rounded-full border-2 border-white shadow-sm"></div>
                            </div>
                        @endforeach
                    </div>
                </div>

                <div class="w-2/3 flex flex-col bg-white">
                    <div class="p-4 border-b font-bold text-lg text-gray-800 shadow-sm" id="chat-title">Pilih obrolan...</div>
                    <div id="chat-messages" class="flex-1 p-6 overflow-y-auto bg-slate-50 flex flex-col gap-4"></div>
                    
                    <form onsubmit="event.preventDefault(); window.sendMsg();" class="p-4 border-t flex gap-2">
                        <input type="text" id="message-input" class="flex-1 border-gray-300 rounded-xl px-4" placeholder="Tulis pesan..." autocomplete="off">
                        <button class="bg-indigo-600 text-white px-6 py-2 rounded-xl font-bold">Kirim</button>
                    </form>
                </div>

            </div>
        </div>
    </div>

    @push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pusher/8.3.0/pusher.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/laravel-echo@1.16.1/dist/echo.iife.js"></script>
    
    <script>
        window.activeChat = { type: null, id: null };
        const authId = {{ Auth::id() }};
        
        // Setup CSRF Token untuk Axios secara global agar aman dari HTTP 419
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
        if (csrfToken) {
            axios.defaults.headers.common['X-CSRF-TOKEN'] = csrfToken;
        }

        // --- REALTIME ENGINE (LARAVEL ECHO MANDIRI VIA CDN) ---
        if (typeof LaravelEcho !== 'undefined') {
            window.Echo = new LaravelEcho({
                broadcaster: 'reverb',
                key: '{{ env("VITE_REVERB_APP_KEY") }}',
                wsHost: window.location.hostname,
                wsPort: {{ env("VITE_REVERB_PORT", 8080) }},
                wssPort: {{ env("VITE_REVERB_PORT", 8080) }},
                forceTLS: false,
                enabledTransports: ['ws', 'wss'],
            });

            // --- RADAR STATUS ONLINE ---
            window.Echo.join('chat')
                .here((users) => {
                    users.forEach(u => {
                        if(u.id !== authId) {
                            document.getElementById(`status-${u.id}`)?.classList.remove('hidden');
                        }
                    });
                })
                .joining((user) => {
                    if(user.id !== authId) {
                        document.getElementById(`status-${user.id}`)?.classList.remove('hidden');
                    }
                })
                .leaving((user) => {
                    document.getElementById(`status-${user.id}`)?.classList.add('hidden');
                })
                // --- PENDENGAR PESAN MASUK LIVE (ANTI REFRESH-REFRESH CLUB) ---
                .listen('MessageSent', (e) => {
                    const m = e.message;
                    // Jika pesan masuk ditujukan ke room chat yang sedang kamu buka sekarang
                    if (
                        (window.activeChat.type === 'user' && m.sender_id == window.activeChat.id && m.receiver_id == authId) ||
                        (window.activeChat.type === 'group' && m.group_id == window.activeChat.id)
                    ) {
                        appendMsg(m);
                    }
                });
        } else {
            console.error("Gagal memuat sistem realtime: Library Echo CDN tidak terbaca.");
        }

        // --- PILIH OBROLAN (USER / GROUP) ---
        window.selectChat = (type, id, name) => {
            window.activeChat = { type, id };
            document.getElementById('chat-title').innerText = type === 'group' ? `Group: ${name}` : name;
            
            // Atur highlight ring pada sidebar yang aktif
            document.querySelectorAll('.sidebar-item').forEach(el => el.classList.remove('ring-2', 'ring-indigo-500'));
            document.getElementById(`${type}-${id}`)?.classList.add('ring-2', 'ring-indigo-500');

            const url = type === 'user' ? `/messages/${id}` : `/messages/group/${id}`;
            axios.get(url).then(res => {
                const box = document.getElementById('chat-messages');
                box.innerHTML = '';
                res.data.forEach(m => appendMsg(m));
                box.scrollTop = box.scrollHeight;
            }).catch(err => {
                console.error("Gagal mengambil pesan:", err);
            });
        };

        // --- TAMPILKAN PESAN KE LAYAR ---
        function appendMsg(m) {
            const isMe = m.sender_id == authId;
            const div = document.createElement('div');
            div.className = `max-w-[75%] p-3 rounded-2xl shadow-sm ${isMe ? 'bg-indigo-600 text-white self-end rounded-tr-none' : 'bg-white border text-gray-800 self-start rounded-tl-none'}`;
            
            const senderName = m.sender ? m.sender.name : 'Sistem';
            div.innerHTML = `<div class="text-[10px] font-bold opacity-75 mb-1 ${isMe ? 'text-indigo-200' : 'text-indigo-600'}">${isMe ? 'Anda' : senderName}</div><div>${m.message}</div>`;
            
            const container = document.getElementById('chat-messages');
            container.appendChild(div);
            container.scrollTop = container.scrollHeight;
        }

        // --- KIRIM PESAN ---
        window.sendMsg = () => {
            const input = document.getElementById('message-input');
            if (!input.value.trim() || !window.activeChat.id) return;
            
            axios.post('/messages', {
                message: input.value,
                receiver_id: window.activeChat.type === 'user' ? window.activeChat.id : null,
                group_id: window.activeChat.type === 'group' ? window.activeChat.id : null
            }).then(res => {
                appendMsg(res.data.message);
                input.value = '';
            }).catch(err => {
                alert("Gagal mengirim pesan.");
            });
        };

        // --- BUAT GRUP BARU ---
        window.createGroup = function() {
            const name = prompt("Nama Grup Baru:");
            if (!name || name.trim() === "") return;

            axios.post('/groups', { 
                name: name.trim() 
            })
            .then(response => {
                alert("Grup berhasil dibuat!");
                window.location.reload();
            })
            .catch(error => {
                console.error("Detail Error Lengkap:", error.response);
                if (error.response && error.response.data && error.response.data.message) {
                    alert(error.response.data.message);
                } else {
                    alert("Gagal membuat grup. Silakan buka inspect (F12) untuk melihat detail error di tab Console.");
                }
            });
        }
    </script>
    @endpush
</x-app-layout>