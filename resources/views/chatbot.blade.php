@extends('layouts.admin')

@section('main-content')
    {{-- CSRF Token Meta Tag --}}
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <div class="card shadow-lg mb-4">
        <div class="card-header bg-primary text-white py-3">
            <div class="d-flex justify-content-between align-items-center">
                <h6 class="m-0 font-weight-bold">Sales Forecasting Chatbot</h6>
                {{-- Help Icon --}}
                <i id="helpIcon" class="fa-regular fa-circle-question" style="font-size: 1.2rem; cursor: pointer;" title="Bantuan"></i>
            </div>
        </div>
        <div class="card-body">
            {{-- Chatbox --}}
            <div id="chatbox"
                style="height: 450px; overflow-y: auto; margin-bottom: 20px; padding: 20px; background-color: #f4f6f9; border-radius: 12px; box-shadow: inset 0 0 5px rgba(0,0,0,0.1);">
            </div>

            {{-- Input Area --}}
            <div style="position: relative; display: flex; align-items: flex-end;">
                <textarea id="userInput" class="form-control shadow-sm" placeholder="Tunggu sebentar..." rows="1"
                    style="resize: none; border-radius: 20px; padding: 10px 50px 10px 15px; overflow-y: auto; min-height: 50px; line-height: 1.5; white-space: pre-wrap;"></textarea>

                <button id="sendButton" class="btn btn-primary"
                    style="position: absolute; right: 8px; bottom: 8px; border-radius: 50%; width: 36px; height: 36px; padding: 0; display: flex; align-items: center; justify-content: center; border: none; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
                    <i id="sendIcon" class="fas fa-arrow-up" style="font-size: 16px;"></i>
                    <span id="loadingSpinner" class="spinner-border spinner-border-sm d-none" style="position: absolute;"
                        role="status" aria-hidden="true"></span>
                </button>
            </div>
        </div>
    </div>

    <!-- Help Chatbot -->
    <div class="modal fade" id="helpModal" tabindex="-1" role="dialog" aria-labelledby="helpModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="helpModalLabel"><i class="fa-regular fa-circle-question mr-2"></i>Panduan Penggunaan Chatbot</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <p>Anda dapat menanyakan berbagai hal terkait data penjualan. Berikut adalah beberapa contoh pertanyaan yang bisa Anda ajukan:</p>
                    <ul>
                        <li>berapa total penjualan di bulan juni 2010?</li>
                        <li>berapa total penjualan tahun 2011 toko 1 departemen 1?</li>
                        <li>pada tahun 2010 toko mana yang penjualannya terbanyak?</li>
                        <li>pada tahun 2010 departemen mana yang penjualannya terbanyak dan di toko mana?</li>
                        <li>penjualan tanggal 5 februari 2011 dept 1 departemen 1 berapa?</li>
                        <li>pada toko 1 departemen mana dengan penjualannya terbanyak?</li>
                        <li>penjualan terbanyak antara toko 1, 2, dan 3 di tahun 2010?</li>
                        <li>di departemen berapa penjualan terbanyak pada toko 1, 2, dan 3 di tahun 2010?</li>
                    </ul>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Tutup</button>
                </div>
            </div>
        </div>
    </div>

    <style>
        #userInput {
            scrollbar-width: thin;
            /* For Firefox */
            scrollbar-color: #888 transparent;
            /* For Firefox */
            transition: height 0.2s;
        }

        #userInput::-webkit-scrollbar {
            /* For Chrome, Safari, Edge */
            width: 8px;
            scrollbar-width: none;
        }

        #userInput::-webkit-scrollbar-thumb {
            background-color: #888;
            border-radius: 4px;
        }

        #userInput::-webkit-scrollbar-track {
            background: transparent;
        }

        /* Chatbox scrollbar styling */
        #chatbox::-webkit-scrollbar {
            width: 8px;
        }

        #chatbox::-webkit-scrollbar-thumb {
            background-color: #c1c1c1;
            border-radius: 4px;
        }

        #chatbox::-webkit-scrollbar-track {
            background: #f4f6f9;
        }

        #chatbox {
            scrollbar-width: thin;
            /* For Firefox */
            scrollbar-color: #c1c1c1 #f4f6f9;
            /* For Firefox */
        }
    </style>
@endsection

@push('scripts')
    {{-- Font Awesome for Help Icon --}}
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" xintegrity="sha512-DTOQO9RWCH3ppGqcWaEA1BIZOC6xxalwEsw9c2QQeAIftl+Vegovlnee1c9QX4TctnWMn13TZye+giMm8e2LwA==" crossorigin="anonymous" referrerpolicy="no-referrer" />

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            // DOM Elements
            const chatbox = document.getElementById('chatbox');
            const userInput = document.getElementById('userInput');
            const sendButton = document.getElementById('sendButton');
            const sendIcon = document.getElementById('sendIcon');
            const loadingSpinner = document.getElementById('loadingSpinner');
            const helpIcon = document.getElementById('helpIcon'); // Help Icon

            // --- Helper Functions ---

            /**
             * Adds a message to the chatbox.
             * @param {string} text - The message text (can be simple HTML).
             * @param {string} sender - 'user' or 'bot'.
             * @param {string|null} id - Optional ID for the message wrapper.
             */
            function addMessage(text, sender = 'user', id = null) {
                const messageWrapper = document.createElement('div');
                if (id) {
                    messageWrapper.id = id;
                }
                messageWrapper.classList.add('message-wrapper', 'd-flex', 'mb-2');
                if (sender === 'user') {
                    messageWrapper.classList.add('justify-content-end');
                } else {
                    messageWrapper.classList.add('justify-content-start');
                }

                const messageDiv = document.createElement('div');
                messageDiv.classList.add('p-2', 'rounded', 'shadow-sm');
                messageDiv.style.maxWidth = '75%';
                messageDiv.style.wordBreak = 'break-word';

                if (sender === 'bot') {
                    messageDiv.classList.add('bg-light', 'text-dark');
                    messageDiv.innerHTML = text;
                } else {
                    messageDiv.classList.add('bg-primary', 'text-white');
                    const escapedText = text.replace(/</g, "&lt;").replace(/>/g, "&gt;");
                    messageDiv.innerHTML = escapedText.replace(/\n/g, '<br>');
                }

                messageWrapper.appendChild(messageDiv);
                chatbox.appendChild(messageWrapper);
                chatbox.scrollTop = chatbox.scrollHeight;
            }

            /**
             * Types out a welcome message in the chatbox.
             * @param {string} message - The welcome message text.
             * @param {function} callback - Function to call after typing is complete.
             */
            function typeWelcomeMessage(message, callback) {
                const messageWrapper = document.createElement('div');
                messageWrapper.classList.add('message-wrapper', 'd-flex', 'mb-2', 'justify-content-start');

                const messageBubble = document.createElement('div');
                messageBubble.classList.add('p-2', 'rounded', 'shadow-sm', 'bg-light', 'text-dark');
                messageBubble.style.maxWidth = '75%';
                messageBubble.style.wordBreak = 'break-word';

                messageWrapper.appendChild(messageBubble);
                chatbox.appendChild(messageWrapper);

                let i = 0;

                function type() {
                    if (i < message.length) {
                        messageBubble.textContent += message.charAt(i);
                        i++;
                        chatbox.scrollTop = chatbox.scrollHeight;
                        setTimeout(type, 30);
                    } else {
                        if (callback) callback();
                    }
                }
                type();
            }

            /**
             * Auto-resizes the textarea height based on its content.
             * @param {HTMLTextAreaElement} textarea - The textarea element.
             */
            function autoResize(textarea) {
                textarea.style.height = 'auto';
                textarea.style.height = Math.min(textarea.scrollHeight, 200) + 'px';
            }

            /**
             * Sends the user's message to the chatbot backend and displays the response.
             */
            async function sendMessage() {
                const messageText = userInput.value.trim();
                if (!messageText) return;
                addMessage(messageText, 'user');
                userInput.value = '';
                autoResize(userInput);
                sendIcon.classList.add('d-none');
                loadingSpinner.classList.remove('d-none');
                sendButton.disabled = true;
                userInput.disabled = true;

                const loadingMessageId = 'loading-' + Date.now();
                addMessage('<em>Sedang memproses...</em>', 'bot',
                    loadingMessageId);

                try {
                    const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
                    const response = await fetch(
                        "{{ route('chatbot.response') }}", {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': csrfToken,
                                'Accept': 'application/json'
                            },
                            body: JSON.stringify({
                                message: messageText
                            })
                        });

                    const loadingElem = document.getElementById(loadingMessageId);
                    if (loadingElem) loadingElem.remove();

                    if (!response.ok) {
                        let errorText = `Maaf, terjadi kesalahan server (${response.status}). Coba lagi nanti.`;
                        try {
                            const errorData = await response.json();
                            if (errorData && errorData.response) {
                                errorText = errorData.response;
                            } else if (errorData && errorData.message) {
                                errorText = `Error ${response.status}: ${errorData.message}`;
                            }
                        } catch (e) {

                            console.warn('Could not parse error response as JSON.', e);
                        }
                        console.error('Server error:', response.status, errorText);
                        addMessage(errorText, 'bot');
                        return;
                    }

                    const data = await response.json();

                    if (data && data.response) {
                        addMessage(data.response, 'bot');
                    } else {
                        console.error('Invalid response format from server:', data);
                        addMessage("Maaf, format respons dari server tidak dikenali.", 'bot');
                    }

                } catch (error) {
                    console.error('Fetch Error:', error);
                    const loadingElem = document.getElementById(loadingMessageId);
                    if (loadingElem) loadingElem.remove();
                    addMessage("Maaf, terjadi kesalahan jaringan. Periksa koneksi Anda dan coba lagi.", 'bot');
                } finally {
                    sendIcon.classList.remove('d-none');
                    loadingSpinner.classList.add('d-none');
                    sendButton.disabled = false;
                    userInput.disabled = false;
                    userInput.focus();
                }
            }

            // --- Event Listeners ---

            // Show help modal on icon click
            if (helpIcon) {
                helpIcon.addEventListener('click', () => {
                    // Using jQuery for Bootstrap modal, as it's common in AdminLTE
                    $('#helpModal').modal('show');
                });
            }

            // Send message on button click
            if (sendButton) {
                sendButton.addEventListener('click', sendMessage);
            }

            // Auto-resize textarea on input
            if (userInput) {
                userInput.addEventListener('input', function() {
                    autoResize(this);
                });

                // Handle Enter key for sending message and Shift+Enter for new line
                userInput.addEventListener('keydown', function(event) {
                    if (event.key === 'Enter' && event.shiftKey) {
                        return;
                    }
                    if (event.key === 'Enter') {
                        event.preventDefault();
                        sendMessage();
                    }
                });
            }


            if (userInput && sendButton && chatbox && sendIcon && loadingSpinner) {

                userInput.disabled = true;
                sendButton.disabled = true;
                autoResize(userInput);

                const welcomeMessage =
                    "Halo! Aku adalah chatbot Sales Forecasting. Ada yang bisa aku bantu hari ini?";
                typeWelcomeMessage(welcomeMessage, () => {
                    userInput.placeholder = "Ketik pesan...";
                    userInput.disabled = false;
                    sendButton.disabled = false;
                    userInput.focus();
                    autoResize(userInput);
                });
            } else {
                console.error("Satu atau lebih elemen DOM chatbot tidak ditemukan. Periksa ID elemen Anda.");
            }
        });
    </script>
@endpush
