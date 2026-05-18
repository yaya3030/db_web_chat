<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Real-time Chat') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg flex" style="height: 600px;">
                
                <div class="w-1/3 border-r p-4 overflow-y-auto bg-gray-50">
                    <h3 class="font-bold mb-4 text-gray-700 border-b pb-2">Users</h3>
                    <div class="flex flex-col gap-2">
                        @foreach($users as $user)
                            <div onclick="window.selectUser({{ $user->id }}, '{{ $user->name }}')" 
                                 id="user-item-{{ $user->id }}"
                                 class="user-item p-3 bg-white border rounded-lg shadow-sm hover:bg-blue-50 cursor-pointer transition">
                                <div class="flex items-center gap-3">
                                    <div class="w-10 h-10 rounded-full bg-blue-500 flex items-center justify-center text-white font-bold">
                                        {{ strtoupper(substr($user->name, 0, 1)) }}
                                    </div>
                                    <span class="font-medium text-gray-800">{{ $user->name }}</span>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>

                <div class="w-2/3 flex flex-col justify-between bg-white">
                    <div class="p-4 border-b bg-gray-50 flex items-center justify-between">
                        <h3 id="chat-with" class="font-bold text-gray-700 text-lg">Pilih teman untuk memulai chat</h3>
                        <span id="status-indicator" class="text-xs text-gray-400"></span>
                    </div>

                    <div id="chat-messages" class="flex-1 p-4 overflow-y-auto flex flex-col gap-3 bg-white" style="background-image: url('https://www.transparenttextures.com/patterns/cubes.png');">
                        <div class="text-center text-gray-400 mt-10">Belum ada percakapan.</div>
                    </div>

                    <div class="p-4 border-t bg-gray-50">
                        <div class="flex gap-2">
                            <input type="text" id="message-input" 
                                   class="flex-1 border-gray-300 rounded-lg shadow-sm focus:border-blue-500 focus:ring-blue-500" 
                                   placeholder="Tulis pesan..."
                                   onkeypress="if(event.key === 'Enter') window.sendMsg()">
                            <button onclick="window.sendMsg()" 
                                    class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded-lg font-bold transition shadow-md">
                                Kirim
                            </button>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>

    @push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>

    <script>
        // Variabel Global
        window.currentReceiverId = null;
        const authId = {{ Auth::id() }};

        // 1. Inisialisasi Echo (Pengecekan Berulang sampai Siap)
        function initEcho() {
            if (typeof window.Echo !== 'undefined') {
                console.log('✅ Echo siap digunakan!');
                window.Echo.private(`chat.${authId}`)
                    .listen('MessageSent', (e) => {
                        console.log('📩 Pesan masuk:', e.message);
                        // Jika sedang membuka chat dengan pengirim tersebut
                        if (window.currentReceiverId == e.message.sender_id) {
                            appendMessage(e.message, 'received');
                        } else {
                            // Notifikasi sederhana jika sedang di chat user lain
                            alert('Pesan baru dari ' + e.message.sender_id);
                        }
                    });
            } else {
                console.log('⏳ Menunggu Echo dimuat...');
                setTimeout(initEcho, 500);
            }
        }

        initEcho();

        // 2. Fungsi Memilih Teman Chat
        window.selectUser = function(id, name) {
            window.currentReceiverId = id;
            
            // UI Feedback
            document.getElementById('chat-with').innerText = "Chat dengan: " + name;
            document.querySelectorAll('.user-item').forEach(el => el.classList.remove('bg-blue-100', 'border-blue-500'));
            document.getElementById('user-item-' + id).classList.add('bg-blue-100', 'border-blue-500');
            
            document.getElementById('chat-messages').innerHTML = '<div class="text-center text-gray-400">Memuat pesan...</div>';

            // Ambil riwayat chat dari server
            axios.get(`/messages/${id}`).then(res => {
                document.getElementById('chat-messages').innerHTML = '';
                if(res.data.length === 0) {
                    document.getElementById('chat-messages').innerHTML = '<div class="text-center text-gray-400 mt-10">Mulailah percakapan pertama!</div>';
                }
                res.data.forEach(msg => {
                    let type = msg.sender_id == authId ? 'sent' : 'received';
                    appendMessage(msg, type);
                });
            }).catch(err => {
                console.error('Gagal memuat pesan:', err);
            });
        }

        axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';
        axios.defaults.headers.common['X-CSRF-TOKEN'] = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

        // 3. Fungsi Mengirim Pesan
        window.sendMsg = function() {
            let input = document.getElementById('message-input');
            let messageText = input.value.trim();
            
            if(!messageText) return;
            if(!window.currentReceiverId) {
                alert('Pilih teman chat terlebih dahulu!');
                return;
            }

            let data = { 
                receiver_id: window.currentReceiverId, 
                message: messageText 
            };
            
            // Kosongkan input segera untuk UX yang lebih baik
            input.value = '';

            axios.post('/messages', data).then(res => {
                // Tampilkan di layar sendiri
                appendMessage(res.data.message, 'sent');
            }).catch(err => {
                console.error('Gagal mengirim:', err);
                alert('Gagal mengirim pesan.');
            });
        }

        // 4. Fungsi Menampilkan Bubble Chat
        function appendMessage(msg, type) {
            let container = document.getElementById('chat-messages');
            
            // Hapus placeholder "Belum ada percakapan" jika ada
            if (container.querySelector('.text-center')) {
                container.innerHTML = '';
            }

            let div = document.createElement('div');
            
            // Styling bubble chat
            if (type === 'sent') {
                div.className = 'self-end bg-blue-600 text-white p-3 rounded-l-lg rounded-tr-lg max-w-md shadow-sm mb-1';
            } else {
                div.className = 'self-start bg-gray-200 text-gray-800 p-3 rounded-r-lg rounded-tl-lg max-w-md shadow-sm mb-1';
            }
            
            div.innerText = msg.message;
            container.appendChild(div);
            
            // Scroll otomatis ke bawah
            container.scrollTop = container.scrollHeight;
        }
    </script>
    @endpush

    <style>
        #chat-messages {
            display: flex;
            flex-direction: column;
            scroll-behavior: smooth;
        }
        .user-item:hover {
            transform: translateY(-1px);
        }
    </style>
</x-app-layout>