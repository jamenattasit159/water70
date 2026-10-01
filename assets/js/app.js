// assets/js/app.js - Modern UI helpers & Chained Dropdown logic
document.addEventListener('DOMContentLoaded', function () {
    // 1. Password Visibility Toggle
    document.querySelectorAll('.password-toggle-btn').forEach(btn => {
        btn.addEventListener('click', function (e) {
            e.preventDefault();
            const input = this.parentElement.querySelector('input');
            if (!input) return;

            if (input.type === 'password') {
                input.type = 'text';
                this.innerHTML = `
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"></path>
                        <line x1="1" y1="1" x2="23" y2="23"></line>
                    </svg>
                `;
            } else {
                input.type = 'password';
                this.innerHTML = `
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                        <circle cx="12" cy="12" r="3"></circle>
                    </svg>
                `;
            }
        });
    });

    // 2. Chained Dropdown: District -> Organizations
    const districtSelect = document.getElementById('district_select');
    const orgSelect = document.getElementById('organization_select');
    const customOrgGroup = document.getElementById('custom_org_group');
    const customOrgInput = document.getElementById('custom_org_input');

    if (districtSelect && orgSelect) {
        districtSelect.addEventListener('change', function () {
            const districtId = this.value;
            loadOrganizations(districtId);
        });

        // If there's an initial district selected (e.g. edit mode or prefilled), trigger load
        if (districtSelect.value) {
            const initialOrgId = orgSelect.getAttribute('data-selected-id');
            loadOrganizations(districtSelect.value, initialOrgId);
        }

        // Toggle custom organization input if "other" selected
        orgSelect.addEventListener('change', function () {
            if (this.value === 'custom') {
                if (customOrgGroup) customOrgGroup.style.display = 'block';
                if (customOrgInput) customOrgInput.focus();
            } else {
                if (customOrgGroup) customOrgGroup.style.display = 'none';
                if (customOrgInput) customOrgInput.value = '';
            }
        });
    }

    function loadOrganizations(districtId, preselectedId = null) {
        if (!orgSelect) return;

        if (!districtId) {
            orgSelect.innerHTML = '<option value="">-- กรุณาเลือกอำเภอก่อน --</option>';
            orgSelect.disabled = true;
            if (customOrgGroup) customOrgGroup.style.display = 'none';
            return;
        }

        orgSelect.disabled = true;
        orgSelect.innerHTML = '<option value="">กำลังโหลดรายชื่อหน่วยงานในพื้นที่...</option>';

        fetch(`api/get_organizations.php?district_id=${districtId}`)
            .then(res => res.json())
            .then(res => {
                if (res.status === 'success' && res.data) {
                    let html = '<option value="">-- เลือกหน่วยงานในสังกัด --</option>';

                    // Group by type for clarity (Only Health Facilities: รพ.สต., สสอ., โรงพยาบาล, สสจ.)
                    const groups = {
                        'hospital': 'โรงพยาบาลทั่วไป / โรงพยาบาลชุมชน (รพ.)',
                        'health_office': 'สำนักงานสาธารณสุขอำเภอ (สสอ.)',
                        'health_center': 'โรงพยาบาลส่งเสริมสุขภาพตำบล (รพ.สต.)',
                        'provincial_office': 'สำนักงานสาธารณสุขจังหวัด (สสจ.)'
                    };

                    const categorized = {};
                    res.data.forEach(item => {
                        const type = item.type || 'general';
                        if (!categorized[type]) categorized[type] = [];
                        categorized[type].push(item);
                    });

                    for (const [typeKey, typeLabel] of Object.entries(groups)) {
                        if (categorized[typeKey] && categorized[typeKey].length > 0) {
                            html += `<optgroup label="${typeLabel}">`;
                            categorized[typeKey].forEach(org => {
                                const isSel = (preselectedId && preselectedId == org.id) ? 'selected' : '';
                                html += `<option value="${org.id}" ${isSel}>${escapeHtml(org.name_th)}</option>`;
                            });
                            html += `</optgroup>`;
                        }
                    }

                    // Add Custom Option
                    html += `<optgroup label="ระบุเอง">
                        <option value="custom">-- อื่น ๆ (ระบุชื่อหน่วยงานเอง) --</option>
                    </optgroup>`;

                    orgSelect.innerHTML = html;
                    orgSelect.disabled = false;

                    if (preselectedId === 'custom') {
                        orgSelect.value = 'custom';
                        if (customOrgGroup) customOrgGroup.style.display = 'block';
                    }
                } else {
                    orgSelect.innerHTML = '<option value="">ไม่พบข้อมูลหน่วยงาน</option>';
                }
            })
            .catch(err => {
                console.error('Error fetching organizations:', err);
                orgSelect.innerHTML = '<option value="">เกิดข้อผิดพลาดในการโหลด</option>';
            });
    }

    // 3. Login Form AJAX Handler
    const loginForm = document.getElementById('login_form');
    if (loginForm) {
        loginForm.addEventListener('submit', function (e) {
            e.preventDefault();
            const submitBtn = loginForm.querySelector('button[type="submit"]');
            const alertBox = document.getElementById('login_alert');
            const formData = new FormData(loginForm);

            setLoading(submitBtn, true, 'กำลังตรวจสอบข้อมูล...');
            if (alertBox) alertBox.style.display = 'none';

            fetch('api/auth.php?action=login', {
                method: 'POST',
                body: formData
            })
                .then(res => res.json())
                .then(data => {
                    setLoading(submitBtn, false, 'เข้าสู่ระบบ');
                    if (data.status === 'success') {
                        showToast('success', data.message || 'เข้าสู่ระบบสำเร็จ');
                        setTimeout(() => {
                            window.location.href = data.redirect || 'dashboard.php';
                        }, 800);
                    } else if (data.status === 'pending') {
                        showAlert(alertBox, 'warning', `
                            <div style="font-weight: 600; margin-bottom: 4px;">รอการอนุมัติสิทธิ์การเข้าใช้งาน</div>
                            <div>${escapeHtml(data.message)}</div>
                            <div style="margin-top: 8px; font-size: 0.85rem; color: #78350f;">
                                <strong>หน่วยงาน:</strong> ${escapeHtml(data.user_info.organization_name || '-')}<br>
                                <strong>อำเภอ:</strong> ${escapeHtml(data.user_info.district_name || '-')}<br>
                                <em>กรุณาประสาน Super Admin ประจำจังหวัดเพื่อตรวจสอบและอนุมัติบัญชี</em>
                            </div>
                        `);
                    } else if (data.status === 'rejected') {
                        showAlert(alertBox, 'danger', `
                            <div style="font-weight: 600; margin-bottom: 4px;">บัญชีไม่ผ่านการอนุมัติ</div>
                            <div>${escapeHtml(data.message)}</div>
                        `);
                    } else {
                        showAlert(alertBox, 'danger', data.message || 'เกิดข้อผิดพลาด');
                    }
                })
                .catch(err => {
                    setLoading(submitBtn, false, 'เข้าสู่ระบบ');
                    showAlert(alertBox, 'danger', 'ไม่สามารถเชื่อมต่อเซิร์ฟเวอร์ได้ กรุณาลองใหม่อีกครั้ง');
                });
        });
    }

    // 4. Register Form AJAX Handler
    const registerForm = document.getElementById('register_form');
    if (registerForm) {
        registerForm.addEventListener('submit', function (e) {
            e.preventDefault();
            const submitBtn = registerForm.querySelector('button[type="submit"]');
            const alertBox = document.getElementById('register_alert');
            const successBox = document.getElementById('register_success_card');
            const formData = new FormData(registerForm);

            // Client-side quick check
            const pass = formData.get('password');
            const confirmPass = formData.get('confirm_password');
            if (pass !== confirmPass) {
                showAlert(alertBox, 'danger', 'รหัสผ่านและยืนยันรหัสผ่านไม่ตรงกัน');
                return;
            }

            setLoading(submitBtn, true, 'กำลังบันทึกข้อมูลการสมัคร...');
            if (alertBox) alertBox.style.display = 'none';

            fetch('api/auth.php?action=register', {
                method: 'POST',
                body: formData
            })
                .then(res => res.json())
                .then(data => {
                    setLoading(submitBtn, false, 'ยืนยันการลงทะเบียน');
                    if (data.status === 'success') {
                        if (registerForm) registerForm.style.display = 'none';
                        if (successBox) {
                            successBox.style.display = 'block';
                            successBox.scrollIntoView({ behavior: 'smooth' });
                        }
                        showToast('success', 'ลงทะเบียนสำเร็จ รอการอนุมัติ');
                    } else {
                        showAlert(alertBox, 'danger', data.message || 'เกิดข้อผิดพลาดในการลงทะเบียน');
                        alertBox.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
                    }
                })
                .catch(err => {
                    setLoading(submitBtn, false, 'ยืนยันการลงทะเบียน');
                    showAlert(alertBox, 'danger', 'เกิดข้อผิดพลาดในการเชื่อมต่อเซิร์ฟเวอร์');
                });
        });
    }
});

// Utility functions
function setLoading(button, isLoading, text) {
    if (!button) return;
    if (isLoading) {
        button.disabled = true;
        button.setAttribute('data-orig-text', button.innerHTML);
        button.innerHTML = `
            <svg class="spinner" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="animation: spin 1s linear infinite;">
                <circle cx="12" cy="12" r="10" stroke-opacity="0.25"></circle>
                <path d="M12 2a10 10 0 0 1 10 10" stroke-opacity="1"></path>
            </svg>
            ${text}
        `;
    } else {
        button.disabled = false;
        button.innerHTML = text || button.getAttribute('data-orig-text');
    }
}

function showAlert(container, type, htmlContent) {
    if (!container) return;
    container.className = `gov-alert ${type}`;
    let iconSvg = '';
    if (type === 'danger') {
        iconSvg = `<svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line></svg>`;
    } else if (type === 'warning') {
        iconSvg = `<svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path><line x1="12" y1="9" x2="12" y2="13"></line><line x1="12" y1="17" x2="12.01" y2="17"></line></svg>`;
    } else {
        iconSvg = `<svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"></polyline></svg>`;
    }

    container.innerHTML = `
        <div class="gov-alert-icon">${iconSvg}</div>
        <div style="flex:1;">${htmlContent}</div>
    `;
    container.style.display = 'flex';
}

function showToast(type, message) {
    let container = document.querySelector('.toast-container');
    if (!container) {
        container = document.createElement('div');
        container.className = 'toast-container';
        document.body.appendChild(container);
    }

    const toast = document.createElement('div');
    toast.className = `gov-toast`;

    let color = '#2563eb';
    let icon = `<polyline points="20 6 9 17 4 12"></polyline>`;
    if (type === 'success') {
        color = '#059669';
        icon = `<polyline points="20 6 9 17 4 12"></polyline>`;
    } else if (type === 'error') {
        color = '#dc2626';
        icon = `<circle cx="12" cy="12" r="10"></circle><line x1="15" y1="9" x2="9" y2="15"></line><line x1="9" y1="9" x2="15" y2="15"></line>`;
    }

    toast.innerHTML = `
        <div style="color: ${color}; display: flex; align-items: center;">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">${icon}</svg>
        </div>
        <div style="flex: 1; font-weight: 500;">${escapeHtml(message)}</div>
    `;

    container.appendChild(toast);

    setTimeout(() => {
        toast.style.transition = 'opacity 0.3s, transform 0.3s';
        toast.style.opacity = '0';
        toast.style.transform = 'translateY(10px)';
        setTimeout(() => toast.remove(), 300);
    }, 4000);
}

function escapeHtml(str) {
    if (!str) return '';
    return String(str)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}

// Generic modal close handlers
document.addEventListener('click', function(e) {
    if (e.target.matches('.modal-close, .btn-modal-cancel') || e.target.closest('.modal-close, .btn-modal-cancel')) {
        const modal = e.target.closest('.modal-overlay');
        if (modal) modal.classList.remove('active');
    } else if (e.target.classList.contains('modal-overlay')) {
        e.target.classList.remove('active');
    }
});

const spinStyle = document.createElement('style');
spinStyle.innerHTML = `@keyframes spin { 100% { transform: rotate(360deg); } }`;
document.head.appendChild(spinStyle);

