var API_BASE = window.API_BASE || '../';

function switchTab(tab) {
    document.querySelectorAll('.auth-form').forEach(f => f.classList.remove('active'));
    document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
    document.getElementById(tab + 'Form').classList.add('active');
    const btns = document.querySelectorAll('.tab-btn');
    if (tab === 'login' && btns[0]) btns[0].classList.add('active');
    if (tab === 'register' && btns[1]) btns[1].classList.add('active');
    if (tab === 'register') { toggleStudentFields(); loadDepartments(); }
}

function toggleStudentFields() {
    const role = document.getElementById('regRole').value;
    const isStudent = role === 'student';
    document.getElementById('deptGroup').style.display = isStudent ? 'block' : 'none';
    document.getElementById('levelGroup').style.display = isStudent ? 'block' : 'none';
}

async function loadDepartments() {
    const sel = document.getElementById('regDept');
    if (!sel || sel.options.length > 1) return;
    try {
        const res = await fetch(API_BASE + 'api/department/list.php');
        const data = await res.json();
        if (data.success && data.data) {
            sel.innerHTML = '<option value="">Select Department (Optional)</option>' +
                data.data.map(d => '<option value="' + d.department_name + '">' + d.department_name + '</option>').join('');
        }
    } catch (e) { console.error('Failed to load departments', e); }
}

document.getElementById('loginForm').addEventListener('submit', async (e) => {
    e.preventDefault();
    const btn = e.target.querySelector('button');
    const msg = document.getElementById('loginMessage');
    btn.disabled = true;
    btn.querySelector('.btn-text').style.display = 'none';
    btn.querySelector('.btn-loader').style.display = 'block';
    msg.className = 'message-box';
    try {
        const res = await fetch(API_BASE + 'api/auth/login.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({
                username: document.getElementById('loginUsername').value,
                password: document.getElementById('loginPassword').value
            })
        });
        const data = await res.json();
        if (data.success) {
            msg.textContent = 'Login successful! Redirecting...';
            msg.classList.add('success');
            setTimeout(() => window.location.href = data.data.redirect, 1000);
        } else {
            msg.textContent = data.message;
            msg.classList.add('error');
        }
    } catch (err) {
        msg.textContent = 'Network error. Please check your connection.';
        msg.classList.add('error');
    } finally {
        btn.disabled = false;
        btn.querySelector('.btn-text').style.display = 'block';
        btn.querySelector('.btn-loader').style.display = 'none';
    }
});

document.getElementById('registerForm').addEventListener('submit', async (e) => {
    e.preventDefault();
    const btn = e.target.querySelector('button');
    const msg = document.getElementById('regMessage');
    btn.disabled = true;
    btn.querySelector('.btn-text').style.display = 'none';
    btn.querySelector('.btn-loader').style.display = 'block';
    msg.className = 'message-box';
    try {
        const res = await fetch(API_BASE + 'api/auth/register.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({
                username: document.getElementById('regUsername').value,
                email: document.getElementById('regEmail').value,
                password: document.getElementById('regPassword').value,
                role: document.getElementById('regRole').value,
                full_name: document.getElementById('regName').value,
                department: document.getElementById('regDept').value,
                level: parseInt(document.getElementById('regLevel').value)
            })
        });
        const data = await res.json();
        if (data.success) {
            msg.textContent = 'Account created! Please sign in.';
            msg.classList.add('success');
            setTimeout(() => switchTab('login'), 1500);
        } else {
            msg.textContent = data.message;
            msg.classList.add('error');
        }
    } catch (err) {
        msg.textContent = 'Network error. Please check your connection.';
        msg.classList.add('error');
    } finally {
        btn.disabled = false;
        btn.querySelector('.btn-text').style.display = 'block';
        btn.querySelector('.btn-loader').style.display = 'none';
    }
});
