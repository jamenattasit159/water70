// assets/js/admin.js - Superadmin CRUD & Approval Management (Modern SaaS Linear Style)
document.addEventListener('DOMContentLoaded', function () {
    const userTableBody = document.getElementById('user_table_body');
    const searchInput = document.getElementById('filter_search');
    const districtFilter = document.getElementById('filter_district');
    const statusFilter = document.getElementById('filter_status');
    const roleFilter = document.getElementById('filter_role');
    const refreshBtn = document.getElementById('btn_refresh_users');
    const userCountBadge = document.getElementById('user_count_badge');
    const clearAllFiltersBtn = document.getElementById('btn_clear_all_filters');
    const filterChipsWrap = document.getElementById('filter_chips_wrap');

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

    const deleteModal = document.getElementById('delete_modal');
    const btnConfirmDelete = document.getElementById('btn_confirm_delete');

    // Modal District & Org elements
    const modalDistrict = document.getElementById('modal_district_select');
    const modalOrg = document.getElementById('modal_org_select');
    const modalCustomOrgGroup = document.getElementById('modal_custom_org_group');
    const modalCustomOrgInput = document.getElementById('modal_custom_org_input');

    // Initial load
    loadStats();
    loadUsers();

    // Event listeners
    if (refreshBtn) refreshBtn.addEventListener('click', () => { 
        loadStats(); 
        loadUsers(); 
        showToast('info', 'รีเฟรชข้อมูลล่าสุดเรียบร้อยแล้ว');
    });

    if (districtFilter) districtFilter.addEventListener('change', () => { updateFilterChips(); loadUsers(); });
    if (statusFilter) statusFilter.addEventListener('change', () => { updateFilterChips(); loadUsers(); });
    if (roleFilter) roleFilter.addEventListener('change', () => { updateFilterChips(); loadUsers(); });

    let searchTimer = null;
    if (searchInput) {
        searchInput.addEventListener('input', () => {
            clearTimeout(searchTimer);
            searchTimer = setTimeout(() => {
                updateFilterChips();
                loadUsers();
            }, 300);
        });
    }

    if (clearAllFiltersBtn) {
        clearAllFiltersBtn.addEventListener('click', () => {
            if (searchInput) searchInput.value = '';
            if (districtFilter) districtFilter.value = '';
            if (statusFilter) statusFilter.value = '';
            if (roleFilter) roleFilter.value = '';
            updateFilterChips();
            loadUsers();
        });
    }

    // Filter chips rendering
    window.clearFilter = function(filterKey) {
        if (filterKey === 'search' && searchInput) searchInput.value = '';
        if (filterKey === 'district' && districtFilter) districtFilter.value = '';
        if (filterKey === 'status' && statusFilter) statusFilter.value = '';
        if (filterKey === 'role' && roleFilter) roleFilter.value = '';
        updateFilterChips();
        loadUsers();
    };

    function updateFilterChips() {
        if (!filterChipsWrap) return;
        const chips = [];

        if (searchInput && searchInput.value.trim()) {
            chips.push(`
                <span class="filter-chip">
                    <span>ค้นหา: "${escapeHtml(searchInput.value.trim())}"</span>
                    <button type="button" class="chip-remove" onclick="clearFilter('search')" aria-label="ล้างตัวกรองคำค้น">&times;</button>
                </span>
            `);
        }

        if (districtFilter && districtFilter.value) {
            const text = districtFilter.options[districtFilter.selectedIndex]?.text || '';
            chips.push(`
                <span class="filter-chip">
                    <span>${escapeHtml(text)}</span>
                    <button type="button" class="chip-remove" onclick="clearFilter('district')" aria-label="ล้างตัวกรองอำเภอ">&times;</button>
                </span>
            `);
        }

        if (statusFilter && statusFilter.value) {
            const statusMap = {
                'pending': 'สถานะ: รออนุมัติ',
                'approved': 'สถานะ: อนุมัติแล้ว',
                'rejected': 'สถานะ: ไม่อนุมัติ',
                'suspended': 'สถานะ: ระงับการใช้งาน'
            };
            chips.push(`
                <span class="filter-chip">
                    <span>${statusMap[statusFilter.value] || statusFilter.value}</span>
                    <button type="button" class="chip-remove" onclick="clearFilter('status')" aria-label="ล้างตัวกรองสถานะ">&times;</button>
                </span>
            `);
        }

        if (roleFilter && roleFilter.value) {
            const roleMap = {
                'user': 'บทบาท: เจ้าหน้าที่ทั่วไป',
                'superadmin': 'บทบาท: Super Admin'
            };
            chips.push(`
                <span class="filter-chip">
                    <span>${roleMap[roleFilter.value] || roleFilter.value}</span>
                    <button type="button" class="chip-remove" onclick="clearFilter('role')" aria-label="ล้างตัวกรองบทบาท">&times;</button>
                </span>
            `);
        }

        if (chips.length > 0) {
            filterChipsWrap.innerHTML = `
                <span style="font-size: 0.775rem; color: var(--color-text-subtle);">ตัวกรองที่เลือก:</span>
                ${chips.join('')}
                <button type="button" class="btn btn-sm btn-ghost" id="btn_clear_all_filters" style="font-size: 0.75rem; padding: 0.15rem 0.45rem;">
                    ล้างตัวกรองทั้งหมด
                </button>
            `;
            filterChipsWrap.style.display = 'flex';
            const clearBtn = document.getElementById('btn_clear_all_filters');
            if (clearBtn) {
                clearBtn.addEventListener('click', () => {
                    if (searchInput) searchInput.value = '';
                    if (districtFilter) districtFilter.value = '';
                    if (statusFilter) statusFilter.value = '';
                    if (roleFilter) roleFilter.value = '';
                    updateFilterChips();
                    loadUsers();
                });
            }
        } else {
            filterChipsWrap.innerHTML = '';
            filterChipsWrap.style.display = 'none';
        }
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
        
        // Modern skeleton placeholder rows
        userTableBody.innerHTML = `
            <tr>
                <td colspan="7" style="padding: 1.5rem 1.15rem;">
                    <div style="display: flex; flex-direction: column; gap: 1rem;">
                        <div style="display: flex; align-items: center; gap: 1rem;">
                            <div style="width: 36px; height: 36px; border-radius: 50%; background: var(--color-surface-hover);"></div>
                            <div style="flex: 1; height: 16px; border-radius: 4px; background: var(--color-surface-hover); max-width: 280px;"></div>
                            <div style="width: 120px; height: 16px; border-radius: 4px; background: var(--color-surface-hover);"></div>
                            <div style="width: 80px; height: 22px; border-radius: 12px; background: var(--color-surface-hover);"></div>
                        </div>
                        <div style="display: flex; align-items: center; gap: 1rem;">
                            <div style="width: 36px; height: 36px; border-radius: 50%; background: var(--color-surface-hover);"></div>
                            <div style="flex: 1; height: 16px; border-radius: 4px; background: var(--color-surface-hover); max-width: 220px;"></div>
                            <div style="width: 120px; height: 16px; border-radius: 4px; background: var(--color-surface-hover);"></div>
                            <div style="width: 80px; height: 22px; border-radius: 12px; background: var(--color-surface-hover);"></div>
                        </div>
                    </div>
                </td>
            </tr>
        `;

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
                    if (userCountBadge) {
                        userCountBadge.textContent = `${data.users.length} บัญชี`;
                    }
                } else {
                    userTableBody.innerHTML = `<tr><td colspan="7" style="text-align: center; color: var(--color-danger-text); padding: 2rem;">${escapeHtml(data.message)}</td></tr>`;
                }
            })
            .catch(err => {
                userTableBody.innerHTML = `<tr><td colspan="7" style="text-align: center; color: var(--color-danger-text); padding: 2rem;">เกิดข้อผิดพลาดในการโหลดข้อมูล</td></tr>`;
            });
    }

    function renderUsersTable(users) {
        if (!users || users.length === 0) {
            userTableBody.innerHTML = `
                <tr>
                    <td colspan="7" style="text-align: center; padding: 3.5rem 1rem; color: var(--color-text-muted);">
                        <svg width="42" height="42" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" style="margin-bottom: 0.75rem; opacity: 0.5;">
                            <circle cx="11" cy="11" r="8"></circle>
                            <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                        </svg>
                        <div style="font-weight: 500; font-size: 0.95rem; color: var(--color-text);">ไม่พบข้อมูลผู้ใช้งานตามเงื่อนไขที่ระบุ</div>
                        <div style="font-size: 0.8rem; margin-top: 0.25rem;">ลองเปลี่ยนหรือล้างเงื่อนไขในตัวกรองด้านบน</div>
                    </td>
                </tr>
            `;
            return;
        }

        let html = '';
        users.forEach((u, idx) => {
            // Status badge (Pill with dot indicator)
            let statusBadge = '';
            if (u.status === 'approved') {
                statusBadge = `<span class="badge badge-success"><span class="badge-dot"></span> อนุมัติแล้ว</span>`;
            } else if (u.status === 'pending') {
                statusBadge = `<span class="badge badge-warning"><span class="badge-dot"></span> รออนุมัติ</span>`;
            } else if (u.status === 'rejected') {
                statusBadge = `<span class="badge badge-danger" title="${escapeHtml(u.rejection_reason || '')}"><span class="badge-dot"></span> ไม่อนุมัติ</span>`;
            } else {
                statusBadge = `<span class="badge badge-danger"><span class="badge-dot"></span> ระงับการใช้งาน</span>`;
            }

            // Role badge
            let roleBadge = '';
            if (u.role === 'superadmin') {
                roleBadge = `<span class="badge badge-info" style="font-size: 0.9rem;">Super Admin</span>`;
            } else {
                roleBadge = `<span class="badge" style="background: var(--color-surface-hover); color: var(--color-text-secondary); font-size: 0.9rem;">เจ้าหน้าที่</span>`;
            }

            // Action buttons (Clean icon buttons with large touch target)
            let actionButtons = `<div style="display: flex; align-items: center; justify-content: flex-end; gap: 0.5rem;">`;

            // If pending, show Approve & Reject with text pills
            if (u.status === 'pending') {
                actionButtons += `
                    <button class="btn btn-sm btn-success btn-approve-user" data-id="${u.id}" data-name="${escapeHtml(u.fullname)}" title="อนุมัติสิทธิ์การใช้งาน">
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"></polyline></svg>
                        <span>อนุมัติ</span>
                    </button>
                    <button class="btn btn-sm btn-danger btn-open-reject" data-id="${u.id}" data-name="${escapeHtml(u.fullname)}" title="ไม่อนุมัติคำขอ">
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
                        <span>ปฏิเสธ</span>
                    </button>
                `;
            }

            // Edit button (icon button)
            actionButtons += `
                <button class="btn btn-sm btn-outline btn-edit-user" data-id="${u.id}" title="แก้ไขข้อมูลผู้ใช้" style="min-width: 38px; min-height: 38px; padding: 0;">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                        <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                    </svg>
                </button>
            `;

            // Delete button (icon button)
            actionButtons += `
                <button class="btn btn-sm btn-outline btn-delete-user" data-id="${u.id}" data-name="${escapeHtml(u.fullname)}" title="ลบผู้ใช้" style="color: var(--color-danger-dot); min-width: 38px; min-height: 38px; padding: 0;">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <polyline points="3 6 5 6 21 6"></polyline>
                        <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                    </svg>
                </button>
            `;

            actionButtons += `</div>`;

            // Avatar initial
            const initial = u.fullname ? u.fullname.charAt(0) : 'U';

            html += `
                <tr>
                    <td style="color: var(--color-text-secondary); font-size: 0.95rem; font-weight: 600;">${idx + 1}</td>
                    <td>
                        <div style="display: flex; align-items: center; gap: 0.85rem;">
                            <div style="width: 42px; height: 42px; border-radius: 50%; background: var(--color-surface-hover); color: var(--color-primary); display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 1rem; border: 1.5px solid var(--color-border); flex-shrink: 0;">
                                ${escapeHtml(initial)}
                            </div>
                            <div>
                                <div style="font-weight: 700; font-size: 1.05rem; color: var(--color-text);">${escapeHtml(u.fullname)}</div>
                                <div style="font-size: 0.9rem; color: var(--color-text-secondary); font-weight: 500;">@${escapeHtml(u.username)}</div>
                            </div>
                        </div>
                    </td>
                    <td>
                        <div style="display: flex; align-items: center; gap: 0.45rem; color: var(--color-text); font-size: 1rem; font-weight: 500;">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"></path></svg>
                            <span>${escapeHtml(u.phone)}</span>
                        </div>
                    </td>
                    <td>
                        <div style="font-weight: 600; color: var(--color-text); font-size: 1.025rem;">${escapeHtml(u.district_name || '-')}</div>
                        <div style="font-size: 0.95rem; color: var(--color-text-secondary); max-width: 260px; white-space: normal; line-height: 1.4; margin-top: 2px;">${escapeHtml(u.organization_name || '-')}</div>
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
                const fd = new FormData();
                fd.append('id', id);

                setLoading(this, true, 'กำลังอนุมัติ...');
                fetch('api/admin_actions.php?action=approve_user', { method: 'POST', body: fd })
                    .then(r => r.json())
                    .then(res => {
                        if (res.status === 'success') {
                            showToast('success', res.message || `อนุมัติ ${name} เรียบร้อยแล้ว`);
                            loadStats();
                            loadUsers();
                        } else {
                            showToast('error', res.message);
                        }
                    })
                    .catch(err => {
                        showToast('error', 'ไม่สามารถเชื่อมต่อเซิร์ฟเวอร์ได้');
                    });
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

        // Delete User via Modern Confirm Modal
        document.querySelectorAll('.btn-delete-user').forEach(btn => {
            btn.addEventListener('click', function () {
                const id = this.getAttribute('data-id');
                const name = this.getAttribute('data-name');
                document.getElementById('delete_user_id').value = id;
                document.getElementById('delete_user_name_display').textContent = `"${name}"`;
                openModal(deleteModal);
            });
        });
    }

    // Confirm Delete Action
    if (btnConfirmDelete) {
        btnConfirmDelete.addEventListener('click', function () {
            const id = document.getElementById('delete_user_id').value;
            if (!id) return;

            setLoading(btnConfirmDelete, true, 'กำลังลบ...');
            const fd = new FormData();
            fd.append('id', id);

            fetch('api/admin_actions.php?action=delete_user', { method: 'POST', body: fd })
                .then(r => r.json())
                .then(res => {
                    setLoading(btnConfirmDelete, false, 'ยืนยันลบข้อมูล');
                    closeModal(deleteModal);
                    if (res.status === 'success') {
                        showToast('success', res.message || 'ลบข้อมูลผู้ใช้งานเรียบร้อยแล้ว');
                        loadStats();
                        loadUsers();
                    } else {
                        showToast('error', res.message || 'ไม่สามารถลบผู้ใช้งานได้');
                    }
                })
                .catch(err => {
                    setLoading(btnConfirmDelete, false, 'ยืนยันลบข้อมูล');
                    closeModal(deleteModal);
                    showToast('error', 'เชื่อมต่อเซิร์ฟเวอร์ไม่สำเร็จ');
                });
        });
    }

    // Reject Form Submit
    if (rejectForm) {
        rejectForm.addEventListener('submit', function (e) {
            e.preventDefault();
            const submitBtn = rejectForm.querySelector('button[type="submit"]');
            setLoading(submitBtn, true, 'กำลังบันทึก...');

            const fd = new FormData(rejectForm);
            fetch('api/admin_actions.php?action=reject_user', { method: 'POST', body: fd })
                .then(r => r.json())
                .then(res => {
                    setLoading(submitBtn, false, 'ยืนยันการปฏิเสธ');
                    closeModal(rejectModal);
                    if (res.status === 'success') {
                        showToast('success', res.message);
                        loadStats();
                        loadUsers();
                    } else {
                        showToast('error', res.message);
                    }
                })
                .catch(err => {
                    setLoading(submitBtn, false, 'ยืนยันการปฏิเสธ');
                    showToast('error', 'เชื่อมต่อเซิร์ฟเวอร์ไม่สำเร็จ');
                });
        });
    }

    // Open User Modal for Add
    const btnAddNewUser = document.getElementById('btn_add_new_user');
    if (btnAddNewUser) {
        btnAddNewUser.addEventListener('click', function () {
            if (userModalTitle) userModalTitle.textContent = 'เพิ่มผู้ใช้งานใหม่';
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
                    document.getElementById('crud_username').disabled = true; // username shouldn't be changed
                    document.getElementById('crud_fullname').value = u.fullname;
                    document.getElementById('crud_phone').value = u.phone;
                    document.getElementById('crud_role').value = u.role;
                    document.getElementById('crud_status').value = u.status;
                    document.getElementById('crud_password').value = '';
                    document.getElementById('crud_password').required = false;
                    document.getElementById('crud_password_label').innerHTML = 'รหัสผ่านใหม่ (เว้นว่างไว้หากไม่เปลี่ยน)';

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
            })
            .catch(err => {
                showToast('error', 'ไม่สามารถดึงข้อมูลผู้ใช้ได้');
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
                        showToast('success', res.message || 'บันทึกข้อมูลสำเร็จ');
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
