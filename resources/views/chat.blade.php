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
                                 id="group-{{ $group->id }}" class="sidebar-item p-3 bg-white border rounded-xl cursor-pointer hover:bg-indigo-50 flex items-center gap-3 relative transition">
                                <div class="w-10 h-10 rounded-full bg-emerald-100 flex items-center justify-center font-bold text-emerald-700">#</div>
                                <span class="font-medium text-gray-700">{{ $group->name }}</span>
                            </div>
                        @empty
                            <p class="text-xs italic text-gray-400 pl-2">Belum ada grup.</p>
                        @endforelse
                    </div>

                    <h3 class="text-xs font-bold text-gray-400 mb-2 uppercase tracking-widest">Direct Messages</h3>
                    <div id="user-list" class="space-y-2">
                        @foreach($users as $user)
                            <div onclick="window.selectChat('user', {{ $user->id }}, '{{ $user->name }}')" 
                                 id="user-{{ $user->id }}" class="sidebar-item p-3 bg-white border rounded-xl cursor-pointer hover:bg-indigo-50 flex items-center gap-3 relative transition">
                                <div class="w-10 h-10 rounded-full bg-indigo-100 flex items-center justify-center font-bold text-indigo-700">{{ substr($user->name, 0, 1) }}</div>
                                <span class="font-medium text-gray-700">{{ $user->name }}</span>
                                <div id="status-{{ $user->id }}" class="hidden absolute left-10 bottom-3 w-3 h-3 bg-green-500 rounded-full border-2 border-white shadow-sm"></div>
                            </div>
                        @endforeach
                    </div>
                </div>

                <div class="w-2/3 flex flex-col bg-white">
                    <div class="p-4 border-b bg-white shadow-sm">
                        <div class="font-bold text-lg text-gray-800" id="chat-title">Pilih obrolan...</div>
                        <div class="text-xs text-gray-500 mt-1 hidden" id="group-members-box">
                            Anggota: <span id="group-members-list" class="font-medium text-indigo-600 italic"></span>
                        </div>
                    </div>
                    <div id="chat-messages" class="flex-1 p-6 overflow-y-auto bg-slate-50 flex flex-col gap-4"></div>
                    <form onsubmit="event.preventDefault(); window.sendMsg();" class="p-4 border-t flex gap-2">
                        <input type="text" id="message-input" class="flex-1 border-gray-300 rounded-xl px-4" placeholder="Tulis pesan..." autocomplete="off">
                        <button class="bg-indigo-600 text-white px-6 py-2 rounded-xl font-bold hover:bg-indigo-700">Kirim</button>
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
        // Token CSRF disuntikkan setelah library Axios di atas ter-load sempurna
        axios.defaults.headers.common['X-CSRF-TOKEN'] = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

        window.activeChat = { type: null, id: null };
        const authId = {{ Auth::id() }};
        
        // Inisialisasi Laravel Echo
        window.Echo = new Echo({
            broadcaster: 'reverb',
            key: '{{ env("VITE_REVERB_APP_KEY") }}',
            wsHost: window.location.hostname,
            wsPort: {{ env("VITE_REVERB_PORT", 8080) }},
            forceTLS: false,
            enabledTransports: ['ws', 'wss'],
            authEndpoint: '/broadcasting/auth'
        });

        // 1. PRESENCE TRACKING (Melacak User Online/Offline)
        window.Echo.join('chat')
            .here(users => users.forEach(u => u.id != authId && document.getElementById(`status-${u.id}`)?.classList.remove('hidden')))
            .joining(u => u.id != authId && document.getElementById(`status-${u.id}`)?.classList.remove('hidden'))
            .leaving(u => document.getElementById(`status-${u.id}`)?.classList.add('hidden'));

        // 2. REAL-TIME PRIVATE CHAT (Mendengarkan DM Masuk)
        // Perbaikan: Menghapus tanda titik sebelum nama event MessageSent
        window.Echo.private('user.' + authId)
            .listen('MessageSent', (e) => {
                if (window.activeChat.type === 'user' && e.message.sender_id == window.activeChat.id) {
                    appendMsg(e.message);
                }
            });

        // 3. FUNGSI MEMILIH OBROLAN
        window.selectChat = (type, id, name) => {
            // Putuskan koneksi dari channel grup lama jika berpindah chat grup
            if (window.activeChat.type === 'group') {
                window.Echo.leave('group.' + window.activeChat.id);
            }

            window.activeChat = { type, id };
            document.getElementById('chat-title').innerText = type === 'group' ? `Group: ${name}` : name;
            document.querySelectorAll('.sidebar-item').forEach(el => el.classList.remove('ring-2', 'ring-indigo-500'));
            document.getElementById(`${type}-${id}`)?.classList.add('ring-2', 'ring-indigo-500');

            if (type === 'group') {
                document.getElementById('group-members-box').classList.remove('hidden');
                
                // REAL-TIME GROUP CHAT (Mendengarkan pesan masuk di grup secara dynamic)
                // Perbaikan: Menghapus tanda titik sebelum nama event MessageSent
                window.Echo.private('group.' + id)
                    .listen('MessageSent', (e) => {
                        if (e.message.sender_id != authId) {
                            appendMsg(e.message);
                        }
                    });
                
                axios.get(`/groups/${id}/users`).then(res => {
                    document.getElementById('group-members-list').innerText = res.data.map(u => u.id === authId ? 'Anda' : u.name).join(', ');
                });
            } else {
                document.getElementById('group-members-box').classList.add('hidden');
            }

            // Ambil Riwayat Obrolan
            axios.get(type === 'user' ? `/messages/${id}` : `/messages/group/${id}`).then(res => {
                const box = document.getElementById('chat-messages');
                box.innerHTML = '';
                res.data.forEach(m => appendMsg(m));
                box.scrollTop = box.scrollHeight;
            });
        };

        // 4. MENAMPILKAN PESAN KE LAYAR
        function appendMsg(m) {
            const isMe = m.sender_id == authId;
            const div = document.createElement('div');
            div.className = `max-w-[75%] p-3 rounded-2xl shadow-sm ${isMe ? 'bg-indigo-600 text-white self-end' : 'bg-white border text-gray-800 self-start'}`;
            div.innerHTML = `<div class="text-[10px] opacity-75 mb-1">${isMe ? 'Anda' : (m.sender?.name || 'User')}</div><div>${m.message}</div>`;
            const container = document.getElementById('chat-messages');
            container.appendChild(div);
            container.scrollTop = container.scrollHeight;
        }

        // 5. FUNGSI MENGIRIM PESAN
        window.sendMsg = () => {
            const input = document.getElementById('message-input');
            if (!input.value.trim() || !window.activeChat.id) return;
            
            const payload = {
                message: input.value,
                receiver_id: window.activeChat.type === 'user' ? window.activeChat.id : null,
                group_id: window.activeChat.type === 'group' ? window.activeChat.id : null
            };

            axios.post('/messages', payload).then(res => {
                appendMsg(res.data.message);
                input.value = '';
            });
        };

        // 6. MEMBUAT GRUP BARU
        window.createGroup = () => {
            const name = prompt("Nama Grup Baru:");
            if (name) axios.post('/groups', { name: name.trim() }).then(() => window.location.reload());
        }
    </script>
    @endpush
</x-app-layout>