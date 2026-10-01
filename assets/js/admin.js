// assets/js/admin.js - Superadmin CRUD & Approval Management
document.addEventListener('DOMContentLoaded', function () {
    const userTableBody = document.getElementById('user_table_body');
    const searchInput = document.getElementById('filter_search');
    const districtFilter = document.getElementById('filter_district');
    const statusFilter = document.getElementById('filter_status');
    const roleFilter = document.getElementById('filter_role');
    const refreshBtn = document.getElementById('btn_refresh_users');

    // Stats elements
    const statTotal = document.getElementById('stat_total_users');
    const statPending = document.getElementById('stat_pending_users');
    const statApproved = document.getElementById('stat_approved_users');
    const statRejected = document.getElementById('stat_rejected_users');

    // Modals
    const userModal = document.getElementById('user_modal');
    const userModalTitle = document.getElementById('user_modal_title');
    const userForm = document.getElementById('user_crud_form');

    const rejectModal = document.getElementById('reject_modal');
    const rejectForm = document.getElementById('reject_form');

    // Modal District & Org elements
    const modalDistrict = document.getElementById('modal_district_select');
    const modalOrg = document.getElementById('modal_org_select');
    const modalCustomOrgGroup = document.getElementById('modal_custom_org_group');
    const modalCustomOrgInput = document.getElementById('modal_custom_org_input');

    // Initial load
    loadStats();
    loadUsers();

    // Event listeners
    if (refreshBtn) refreshBtn.addEventListener('click', () => { loadStats(); loadUsers(); });
    if (districtFilter) districtFilter.addEventListener('change', loadUsers);
    if (statusFilter) statusFilter.addEventListener('change', loadUsers);
    if (roleFilter) roleFilter.addEventListener('change', loadUsers);

    let searchTimer = null;
    if (searchInput) {
        searchInput.addEventListener('input', () => {
            clearTimeout(searchTimer);
            searchTimer = setTimeout(loadUsers, 300);
        });
    }

    // Chained dropdown for Modal
    if (modalDistrict && modalOrg) {
        modalDistrict.addEventListener('change', function () {
            loadModalOrgs(this.value);
        });

        modalOrg.addEventListener('change', function () {
            if (this.value === 'custom') {
                if (modalCustomOrgGroup) modalCustomOrgGroup.style.display = 'block';
                if (modalCustomOrgInput) modalCustomOrgInput.focus();
            } else {
                if (modalCustomOrgGroup) modalCustomOrgGroup.style.display = 'none';
                if (modalCustomOrgInput) modalCustomOrgInput.value = '';
            }
        });
    }

    function loadModalOrgs(districtId, preselectedId = null) {
        if (!modalOrg) return;
        if (!districtId) {
            modalOrg.innerHTML = '<option value="">-- กรุณาเลือกอำเภอก่อน --</option>';
            modalOrg.disabled = true;
            if (modalCustomOrgGroup) modalCustomOrgGroup.style.display = 'none';
            return;
        }

        modalOrg.disabled = true;
        modalOrg.innerHTML = '<option value="">กำลังโหลดรายชื่อหน่วยงาน...</option>';

        fetch(`api/get_organizations.php?district_id=${districtId}`)
            .then(res => res.json())
            .then(res => {
                if (res.status === 'success' && res.data) {
                    let html = '<option value="">-- เลือกหน่วยงานในสังกัด --</option>';
                    res.data.forEach(org => {
                        const sel = (preselectedId && preselectedId == org.id) ? 'selected' : '';
                        html += `<option value="${org.id}" ${sel}>${escapeHtml(org.name_th)}</option>`;
                    });
                    html += `<option value="custom">-- อื่น ๆ (ระบุเอง) --</option>`;
                    modalOrg.innerHTML = html;
                    modalOrg.disabled = false;

                    if (preselectedId === 'custom') {
                        modalOrg.value = 'custom';
                        if (modalCustomOrgGroup) modalCustomOrgGroup.style.display = 'block';
                    }
                }
            })
            .catch(err => {
                modalOrg.innerHTML = '<option value="">โหลดข้อมูลไม่สำเร็จ</option>';
            });
    }

    function loadStats() {
        fetch('api/admin_actions.php?action=stats')
            .then(res => res.json())
            .then(data => {
                if (data.status === 'success' && data.stats) {
                    if (statTotal) statTotal.textContent = data.stats.total;
                    if (statPending) statPending.textContent = data.stats.pending;
                    if (statApproved) statApproved.textContent = data.stats.approved;
                    if (statRejected) statRejected.textContent = data.stats.rejected;
                }
            })
            .catch(err => console.error('Error fetching stats:', err));
    }

    function loadUsers() {
        if (!userTableBody) return;
        userTableBody.innerHTML = `<tr><td colspan="7" style="text-align: center; padding: 2rem; color: #64748b;">กำลังโหลดข้อมูลผู้ใช้งาน...</td></tr>`;

        const search = searchInput ? encodeURIComponent(searchInput.value.trim()) : '';
        const district = districtFilter ? districtFilter.value : '';
        const status = statusFilter ? statusFilter.value : '';
        const role = roleFilter ? roleFilter.value : '';

        const url = `api/admin_actions.php?action=list_users&search=${search}&district_id=${district}&status=${status}&role=${role}`;

        fetch(url)
            .then(res => res.json())
            .then(data => {
                if (data.status === 'success') {
                    renderUsersTable(data.users);
                } else {
                    userTableBody.innerHTML = `<tr><td colspan="7" style="text-align: center; color: #dc2626; padding: 1.5rem;">${escapeHtml(data.message)}</td></tr>`;
                }
            })
            .catch(err => {
                userTableBody.innerHTML = `<tr><td colspan="7" style="text-align: center; color: #dc2626; padding: 1.5rem;">เกิดข้อผิดพลาดในการโหลดข้อมูล</td></tr>`;
            });
    }

    function renderUsersTable(users) {
        if (!users || users.length === 0) {
            userTableBody.innerHTML = `
                <tr>
                    <td colspan="7" style="text-align: center; padding: 3rem 1rem; color: #64748b;">
                        <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" style="margin-bottom: 0.5rem; opacity: 0.6;">
                            <circle cx="11" cy="11" r="8"></circle>
                            <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                        </svg>
                        <div style="font-weight: 500;">ไม่พบข้อมูลผู้ใช้งานตามเงื่อนไขที่ระบุ</div>
                    </td>
                </tr>
            `;
            return;
        }

        let html = '';
        users.forEach((u, idx) => {
            // Status badge
            let statusBadge = '';
            if (u.status === 'approved') {
                statusBadge = `<span class="badge badge-approved"><svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"></polyline></svg> อนุมัติแล้ว</span>`;
            } else if (u.status === 'pending') {
                statusBadge = `<span class="badge badge-pending"><svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg> รออนุมัติ</span>`;
            } else if (u.status === 'rejected') {
                statusBadge = `<span class="badge badge-rejected" title="${escapeHtml(u.rejection_reason || '')}"><svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg> ไม่อนุมัติ</span>`;
            } else {
                statusBadge = `<span class="badge badge-suspended">ระงับการใช้งาน</span>`;
            }

            // Role badge
            let roleBadge = '';
            if (u.role === 'superadmin') {
                roleBadge = `<span class="badge badge-role-superadmin">★ Super Admin</span>`;
            } else {
                roleBadge = `<span class="badge badge-role-user">เจ้าหน้าที่ทั่วไป</span>`;
            }

            // Actions buttons
            let actionButtons = `<div style="display: flex; align-items: center; gap: 0.35rem;">`;

            // If pending, show Approve & Reject prominently
            if (u.status === 'pending') {
                actionButtons += `
                    <button class="btn btn-sm btn-success btn-approve-user" data-id="${u.id}" data-name="${escapeHtml(u.fullname)}" title="อนุมัติการใช้งานทันที">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"></polyline></svg> อนุมัติ
                    </button>
                    <button class="btn btn-sm btn-danger btn-open-reject" data-id="${u.id}" data-name="${escapeHtml(u.fullname)}" title="ไม่อนุมัติ / ส่งคืน">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg> ปฏิเสธ
                    </button>
                `;
            }

            // Edit button
            actionButtons += `
                <button class="btn btn-sm btn-outline btn-edit-user" data-id="${u.id}" title="แก้ไขข้อมูลผู้ใช้">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path></svg>
                </button>
            `;

            // Delete button (disabled for self if current admin)
            actionButtons += `
                <button class="btn btn-sm btn-outline btn-delete-user" data-id="${u.id}" data-name="${escapeHtml(u.fullname)}" title="ลบผู้ใช้" style="color: #be123c;">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path></svg>
                </button>
            `;

            actionButtons += `</div>`;

            // Avatar initial
            const initial = u.fullname ? u.fullname.charAt(0) : 'U';

            html += `
                <tr>
                    <td style="color: #64748b; font-size: 0.85rem; width: 40px;">${idx + 1}</td>
                    <td>
                        <div style="display: flex; align-items: center; gap: 0.75rem;">
                            <div style="width: 38px; height: 38px; border-radius: 50%; background: #e2e8f0; color: #1e293b; display: flex; align-items: center; justify-content: center; font-weight: 700; font-family: var(--font-heading);">
                                ${escapeHtml(initial)}
                            </div>
                            <div>
                                <div style="font-weight: 600; color: #0f2c4c;">${escapeHtml(u.fullname)}</div>
                                <div style="font-size: 0.825rem; color: #64748b;">@${escapeHtml(u.username)}</div>
                            </div>
                        </div>
                    </td>
                    <td>
                        <div style="display: flex; align-items: center; gap: 0.35rem; color: #334155;">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"></path></svg>
                            <span>${escapeHtml(u.phone)}</span>
                        </div>
                    </td>
                    <td>
                        <div style="font-weight: 500; color: #0f172a;">${escapeHtml(u.district_name || '-')}</div>
                        <div style="font-size: 0.825rem; color: #64748b; max-width: 250px; white-space: normal;">${escapeHtml(u.organization_name || '-')}</div>
                    </td>
                    <td>${roleBadge}</td>
                    <td>${statusBadge}</td>
                    <td>${actionButtons}</td>
                </tr>
            `;
        });

        userTableBody.innerHTML = html;
        bindActionEvents();
    }

    function bindActionEvents() {
        // Approve Button
        document.querySelectorAll('.btn-approve-user').forEach(btn => {
            btn.addEventListener('click', function () {
                const id = this.getAttribute('data-id');
                const name = this.getAttribute('data-name');
                if (confirm(`ยืนยันการอนุมัติสิทธิ์เข้าใช้งานสำหรับ "${name}" หรือไม่?`)) {
                    const fd = new FormData();
                    fd.append('id', id);

                    fetch('api/admin_actions.php?action=approve_user', { method: 'POST', body: fd })
                        .then(r => r.json())
                        .then(res => {
                            if (res.status === 'success') {
                                showToast('success', res.message);
                                loadStats();
                                loadUsers();
                            } else {
                                showToast('error', res.message);
                            }
                        });
                }
            });
        });

        // Open Reject Modal
        document.querySelectorAll('.btn-open-reject').forEach(btn => {
            btn.addEventListener('click', function () {
                const id = this.getAttribute('data-id');
                const name = this.getAttribute('data-name');
                document.getElementById('reject_user_id').value = id;
                document.getElementById('reject_user_name_display').textContent = name;
                document.getElementById('reject_reason').value = '';
                openModal(rejectModal);
            });
        });

        // Edit User
        document.querySelectorAll('.btn-edit-user').forEach(btn => {
            btn.addEventListener('click', function () {
                const id = this.getAttribute('data-id');
                openEditUserModal(id);
            });
        });

        // Delete User
        document.querySelectorAll('.btn-delete-user').forEach(btn => {
            btn.addEventListener('click', function () {
                const id = this.getAttribute('data-id');
                const name = this.getAttribute('data-name');
                if (confirm(`คำเตือน: คุณแน่ใจหรือไม่ว่าต้องการลบข้อมูลผู้ใช้งาน "${name}" ออกจากระบบ? (การกระทำนี้ไม่สามารถย้อนกลับได้)`)) {
                    const fd = new FormData();
                    fd.append('id', id);

                    fetch('api/admin_actions.php?action=delete_user', { method: 'POST', body: fd })
                        .then(r => r.json())
                        .then(res => {
                            if (res.status === 'success') {
                                showToast('success', res.message);
                                loadStats();
                                loadUsers();
                            } else {
                                showToast('error', res.message);
                            }
                        });
                }
            });
        });
    }

    // Reject Form Submit
    if (rejectForm) {
        rejectForm.addEventListener('submit', function (e) {
            e.preventDefault();
            const fd = new FormData(rejectForm);
            fetch('api/admin_actions.php?action=reject_user', { method: 'POST', body: fd })
                .then(r => r.json())
                .then(res => {
                    closeModal(rejectModal);
                    if (res.status === 'success') {
                        showToast('success', res.message);
                        loadStats();
                        loadUsers();
                    } else {
                        showToast('error', res.message);
                    }
                });
        });
    }

    // Open User Modal for Add
    const btnAddNewUser = document.getElementById('btn_add_new_user');
    if (btnAddNewUser) {
        btnAddNewUser.addEventListener('click', function () {
            if (userModalTitle) userModalTitle.textContent = 'เพิ่มผู้ใช้งานใหม่ (โดย Super Admin)';
            if (userForm) userForm.reset();
            document.getElementById('crud_user_id').value = '0';
            document.getElementById('crud_username').disabled = false;
            document.getElementById('crud_password_label').innerHTML = 'รหัสผ่าน <span class="req">*</span>';
            document.getElementById('crud_password').required = true;
            document.getElementById('crud_status').value = 'approved';

            if (modalDistrict) modalDistrict.value = '';
            if (modalOrg) {
                modalOrg.innerHTML = '<option value="">-- กรุณาเลือกอำเภอก่อน --</option>';
                modalOrg.disabled = true;
            }
            if (modalCustomOrgGroup) modalCustomOrgGroup.style.display = 'none';

            openModal(userModal);
        });
    }

    function openEditUserModal(userId) {
        fetch(`api/admin_actions.php?action=get_user&id=${userId}`)
            .then(r => r.json())
            .then(res => {
                if (res.status === 'success' && res.user) {
                    const u = res.user;
                    if (userModalTitle) userModalTitle.textContent = `แก้ไขข้อมูลผู้ใช้: ${u.fullname}`;
                    document.getElementById('crud_user_id').value = u.id;
                    document.getElementById('crud_username').value = u.username;
                    document.getElementById('crud_username').disabled = true; // username shouldn't be changed easily
                    document.getElementById('crud_fullname').value = u.fullname;
                    document.getElementById('crud_phone').value = u.phone;
                    document.getElementById('crud_role').value = u.role;
                    document.getElementById('crud_status').value = u.status;
                    document.getElementById('crud_password').value = '';
                    document.getElementById('crud_password').required = false;
                    document.getElementById('crud_password_label').innerHTML = 'รหัสผ่านใหม่ (เว้นว่างไว้หากไม่ต้องการเปลี่ยน)';

                    if (modalDistrict) {
                        modalDistrict.value = u.district_id || '';
                        loadModalOrgs(u.district_id, u.organization_id || 'custom');
                        if (!u.organization_id && u.organization_name) {
                            if (modalCustomOrgInput) modalCustomOrgInput.value = u.organization_name;
                        }
                    }

                    openModal(userModal);
                } else {
                    showToast('error', res.message || 'ไม่พบข้อมูลผู้ใช้');
                }
            });
    }

    // User Form Submit (Add or Edit)
    if (userForm) {
        userForm.addEventListener('submit', function (e) {
            e.preventDefault();
            const submitBtn = userForm.querySelector('button[type="submit"]');
            setLoading(submitBtn, true, 'กำลังบันทึก...');

            const fd = new FormData(userForm);
            // If username disabled, append manually
            const unameField = document.getElementById('crud_username');
            if (unameField && unameField.disabled) {
                fd.append('username', unameField.value);
            }

            fetch('api/admin_actions.php?action=save_user', { method: 'POST', body: fd })
                .then(r => r.json())
                .then(res => {
                    setLoading(submitBtn, false, 'บันทึกข้อมูล');
                    if (res.status === 'success') {
                        closeModal(userModal);
                        showToast('success', res.message);
                        loadStats();
                        loadUsers();
                    } else {
                        showToast('error', res.message || 'เกิดข้อผิดพลาด');
                    }
                })
                .catch(err => {
                    setLoading(submitBtn, false, 'บันทึกข้อมูล');
                    showToast('error', 'เชื่อมต่อเซิร์ฟเวอร์ไม่สำเร็จ');
                });
        });
    }

    // Modal helpers
    function openModal(modal) {
        if (!modal) return;
        modal.classList.add('active');
        document.body.style.overflow = 'hidden';
    }

    function closeModal(modal) {
        if (!modal) return;
        modal.classList.remove('active');
        document.body.style.overflow = '';
    }

    document.querySelectorAll('.modal-close, .btn-modal-cancel').forEach(btn => {
        btn.addEventListener('click', function () {
            const modal = this.closest('.modal-overlay');
            closeModal(modal);
        });
    });

    // Close when clicking overlay backdrop
    document.querySelectorAll('.modal-overlay').forEach(overlay => {
        overlay.addEventListener('click', function (e) {
            if (e.target === this) {
                closeModal(this);
            }
        });
    });
});
