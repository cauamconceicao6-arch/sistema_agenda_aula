// API Base dinâmica para funcionar tanto no XAMPP quanto no Laravel Herd
const basePath = window.location.pathname.substring(0, window.location.pathname.indexOf('/frontend/'));
const API_BASE = window.location.origin + basePath + '/backend/index.php/api';

// --- Utility Functions ---
function showAlert(message, type = 'error', elementId = 'alert-box') {
    const alertBox = document.getElementById(elementId);
    if(!alertBox) return;
    alertBox.textContent = message;
    alertBox.className = `mb-4 p-3 rounded text-sm ${type === 'error' ? 'bg-red-100 text-red-700' : 'bg-green-100 text-green-700'} block`;
    setTimeout(() => { alertBox.classList.add('hidden'); alertBox.classList.remove('block'); }, 4000);
}

function getUser() {
    return JSON.parse(localStorage.getItem('agenda_user'));
}

// --- Login / Register Logic ---
if (window.location.pathname.endsWith('index.html') || window.location.pathname.endsWith('/frontend/')) {
    let isLogin = true;

    const toggleFormBtn = document.getElementById('toggle-form');
    const formTitle = document.getElementById('form-title');
    const nameField = document.getElementById('name-field');
    const roleField = document.getElementById('role-field');
    const submitBtn = document.getElementById('submit-btn');
    const authForm = document.getElementById('auth-form');

    if(toggleFormBtn) {
        toggleFormBtn.addEventListener('click', (e) => {
            e.preventDefault();
            isLogin = !isLogin;
            formTitle.textContent = isLogin ? 'Login' : 'Cadastro';
            submitBtn.textContent = isLogin ? 'Entrar' : 'Cadastrar';
            toggleFormBtn.textContent = isLogin ? 'Não tem conta? Cadastre-se' : 'Já tem conta? Entre';
            
            if (isLogin) {
                nameField.classList.add('hidden');
                roleField.classList.add('hidden');
                document.getElementById('name').removeAttribute('required');
            } else {
                nameField.classList.remove('hidden');
                roleField.classList.remove('hidden');
                document.getElementById('name').setAttribute('required', 'true');
            }
        });
    }

    if(authForm) {
        authForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            const email = document.getElementById('email').value;
            const password = document.getElementById('password').value;
            
            if (isLogin) {
                // Fazer Login
                try {
                    const res = await fetch(`${API_BASE}/users/login`, {
                        method: 'POST',
                        body: JSON.stringify({ email, password })
                    });
                    const data = await res.json();
                    
                    if (res.ok) {
                        localStorage.setItem('agenda_user', JSON.stringify(data.user));
                        window.location.href = 'dashboard.html';
                    } else {
                        showAlert(data.message || 'Erro no login');
                    }
                } catch (err) {
                    showAlert('Erro de conexão com o servidor.');
                }
            } else {
                // Fazer Cadastro
                const name = document.getElementById('name').value;
                const role = document.getElementById('role').value;
                try {
                    const res = await fetch(`${API_BASE}/users/register`, {
                        method: 'POST',
                        body: JSON.stringify({ name, email, password, role })
                    });
                    const data = await res.json();
                    
                    if (res.status === 201) {
                        showAlert('Cadastro realizado! Faça o login agora.', 'success');
                        toggleFormBtn.click(); // Volta pro login
                    } else {
                        showAlert(data.message || 'Erro no cadastro');
                    }
                } catch (err) {
                    showAlert('Erro de conexão com o servidor.');
                }
            }
        });
    }
}

// --- Dashboard Logic ---
if (window.location.pathname.endsWith('dashboard.html')) {
    const user = getUser();
    if (!user) {
        window.location.href = 'index.html';
    }

    document.getElementById('user-info').textContent = `Olá, ${user.name} (${user.role === 'instructor' ? 'Professor' : 'Aluno'})`;

    document.getElementById('logout-btn').addEventListener('click', () => {
        localStorage.removeItem('agenda_user');
        window.location.href = 'index.html';
    });

    const mainTitle = document.getElementById('main-title');
    const mainContent = document.getElementById('main-content');
    
    // UI setup based on role
    if (user.role === 'instructor') {
        document.getElementById('instructor-actions').classList.replace('hidden', 'flex');
        
        document.getElementById('btn-show-my-classes').addEventListener('click', loadInstructorClasses);
        document.getElementById('btn-show-create-class').addEventListener('click', () => {
            document.getElementById('create-class-modal').classList.remove('hidden');
        });

        document.getElementById('close-modal-btn').addEventListener('click', () => {
            document.getElementById('create-class-modal').classList.add('hidden');
        });

        document.getElementById('create-class-form').addEventListener('submit', async (e) => {
            e.preventDefault();
            const title = document.getElementById('class-title').value;
            const description = document.getElementById('class-desc').value;
            const start_time = document.getElementById('class-time').value.replace('T', ' ') + ':00';
            const capacity = document.getElementById('class-capacity').value;

            try {
                const res = await fetch(`${API_BASE}/classes`, {
                    method: 'POST',
                    body: JSON.stringify({ instructor_id: user.id, title, description, start_time, capacity })
                });
                if (res.status === 201) {
                    document.getElementById('create-class-modal').classList.add('hidden');
                    showAlert('Aula criada com sucesso!', 'success', 'alert-box-dashboard');
                    loadInstructorClasses();
                } else {
                    showAlert('Erro ao criar aula', 'error', 'alert-box-dashboard');
                }
            } catch (err) {
                showAlert('Erro de conexão', 'error', 'alert-box-dashboard');
            }
        });

        loadInstructorClasses(); // Default view

    } else {
        // Student logic
        document.getElementById('student-actions').classList.replace('hidden', 'flex');
        
        document.getElementById('btn-show-available-classes').addEventListener('click', loadAvailableClasses);
        document.getElementById('btn-show-my-bookings').addEventListener('click', loadMyBookings);

        loadAvailableClasses(); // Default view
    }

    // --- Helper Functions for Views ---

    async function loadInstructorClasses() {
        mainTitle.textContent = "Minhas Aulas";
        mainContent.innerHTML = "<p>Carregando...</p>";
        try {
            const res = await fetch(`${API_BASE}/classes?instructor_id=${user.id}`);
            const classes = await res.json();
            renderClassesList(classes, true);
        } catch (e) {
            mainContent.innerHTML = "<p class='text-red-500'>Erro ao carregar aulas.</p>";
        }
    }

    async function loadAvailableClasses() {
        mainTitle.textContent = "Aulas Disponíveis";
        mainContent.innerHTML = "<p>Carregando...</p>";
        try {
            const res = await fetch(`${API_BASE}/classes`);
            const classes = await res.json();
            renderClassesListForStudent(classes);
        } catch (e) {
            mainContent.innerHTML = "<p class='text-red-500'>Erro ao carregar aulas.</p>";
        }
    }

    async function loadMyBookings() {
        mainTitle.textContent = "Meus Agendamentos";
        mainContent.innerHTML = "<p>Carregando...</p>";
        try {
            const res = await fetch(`${API_BASE}/bookings?student_id=${user.id}`);
            const bookings = await res.json();
            renderBookingsList(bookings);
        } catch (e) {
            mainContent.innerHTML = "<p class='text-red-500'>Erro ao carregar agendamentos.</p>";
        }
    }

    function renderClassesList(classes, isInstructor) {
        if (classes.length === 0) {
            mainContent.innerHTML = "<p class='text-gray-500'>Nenhuma aula encontrada.</p>";
            return;
        }

        let html = '<div class="grid gap-4">';
        classes.forEach(c => {
            html += `
            <div class="border p-4 rounded bg-gray-50 flex justify-between items-center">
                <div>
                    <h3 class="font-bold text-lg text-blue-700">${c.title}</h3>
                    <p class="text-sm text-gray-600">${c.description || 'Sem descrição'}</p>
                    <p class="text-sm font-medium mt-2">Data/Hora: ${new Date(c.start_time).toLocaleString('pt-BR')}</p>
                    <p class="text-sm font-medium">Vagas: ${c.capacity}</p>
                </div>
                ${isInstructor ? `<button onclick="deleteClass(${c.id})" class="bg-red-500 hover:bg-red-600 text-white px-3 py-1 rounded text-sm transition">Excluir</button>` : ''}
            </div>`;
        });
        html += '</div>';
        mainContent.innerHTML = html;
    }

    function renderClassesListForStudent(classes) {
        if (classes.length === 0) {
            mainContent.innerHTML = "<p class='text-gray-500'>Nenhuma aula disponível.</p>";
            return;
        }

        let html = '<div class="grid gap-4 md:grid-cols-2">';
        classes.forEach(c => {
            html += `
            <div class="border p-4 rounded bg-white shadow-sm flex flex-col justify-between">
                <div>
                    <h3 class="font-bold text-lg text-green-700">${c.title}</h3>
                    <p class="text-xs text-gray-500 mb-2">Prof: ${c.instructor_name}</p>
                    <p class="text-sm text-gray-600">${c.description || ''}</p>
                    <p class="text-sm font-medium mt-2">Data/Hora: ${new Date(c.start_time).toLocaleString('pt-BR')}</p>
                    <p class="text-sm font-medium text-blue-600">Vagas: ${c.capacity}</p>
                </div>
                <button onclick="bookClass(${c.id})" class="mt-4 bg-green-500 hover:bg-green-600 text-white px-3 py-2 rounded text-sm font-bold transition">Agendar Aula</button>
            </div>`;
        });
        html += '</div>';
        mainContent.innerHTML = html;
    }

    function renderBookingsList(bookings) {
        if (bookings.length === 0) {
            mainContent.innerHTML = "<p class='text-gray-500'>Você não possui agendamentos.</p>";
            return;
        }

        let html = '<div class="grid gap-4">';
        bookings.forEach(b => {
            html += `
            <div class="border-l-4 border-blue-500 p-4 rounded bg-white shadow-sm flex justify-between items-center">
                <div>
                    <h3 class="font-bold text-lg">${b.title}</h3>
                    <p class="text-sm text-gray-600">Professor: ${b.instructor_name}</p>
                    <p class="text-sm font-medium mt-1">Data/Hora: ${new Date(b.start_time).toLocaleString('pt-BR')}</p>
                </div>
                <button onclick="cancelBooking(${b.booking_id})" class="bg-red-500 hover:bg-red-600 text-white px-3 py-1 rounded text-sm transition">Cancelar</button>
            </div>`;
        });
        html += '</div>';
        mainContent.innerHTML = html;
    }
}

// Global functions for inline onclick handlers
window.deleteClass = async function(id) {
    if(!confirm("Tem certeza que deseja excluir esta aula?")) return;
    try {
        const res = await fetch(`${API_BASE}/classes?id=${id}`, { method: 'DELETE' });
        if (res.ok) {
            showAlert('Aula excluída', 'success', 'alert-box-dashboard');
            document.getElementById('btn-show-my-classes').click();
        } else {
            showAlert('Erro ao excluir', 'error', 'alert-box-dashboard');
        }
    } catch(e) { showAlert('Erro de conexão', 'error', 'alert-box-dashboard'); }
}

window.bookClass = async function(class_id) {
    const user = getUser();
    try {
        const res = await fetch(`${API_BASE}/bookings`, {
            method: 'POST',
            body: JSON.stringify({ class_id: class_id, student_id: user.id })
        });
        const data = await res.json();
        if (res.status === 201) {
            showAlert('Agendamento confirmado!', 'success', 'alert-box-dashboard');
            document.getElementById('btn-show-my-bookings').click();
        } else {
            showAlert(data.message || 'Erro ao agendar', 'error', 'alert-box-dashboard');
        }
    } catch(e) { showAlert('Erro de conexão', 'error', 'alert-box-dashboard'); }
}

window.cancelBooking = async function(booking_id) {
    if(!confirm("Deseja realmente cancelar este agendamento?")) return;
    try {
        const res = await fetch(`${API_BASE}/bookings?id=${booking_id}`, { method: 'DELETE' });
        if (res.ok) {
            showAlert('Agendamento cancelado.', 'success', 'alert-box-dashboard');
            document.getElementById('btn-show-my-bookings').click();
        } else {
            showAlert('Erro ao cancelar', 'error', 'alert-box-dashboard');
        }
    } catch(e) { showAlert('Erro de conexão', 'error', 'alert-box-dashboard'); }
}
