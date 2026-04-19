/**
 * SPACEX Trading Academy — Admin Portal Logic
 * Handles protected interactions, stats dashboard, and file uploads.
 */

document.addEventListener('DOMContentLoaded', () => {
  // 1. Initial Authentication Check
  const token = localStorage.getItem('spacex_token');
  const user = JSON.parse(localStorage.getItem('spacex_user') || '{}');

  if (!token || user.role !== 'admin') {
    window.location.href = 'login.html';
    return;
  }

  // Populate Admin UI Defaults
  document.getElementById('adminEmailDisplay').textContent = user.email || 'admin@spacextrading.com';
  document.getElementById('adminLoader').style.display = 'none';

  // 2. Navigation Logic
  document.querySelectorAll('.sidebar-link').forEach(link => {
    link.addEventListener('click', (e) => {
      e.preventDefault();
      
      // Update Active Class Highlighting
      document.querySelectorAll('.sidebar-link').forEach(l => l.classList.remove('active'));
      link.classList.add('active');

      // Swap Sections
      document.querySelectorAll('.dash-content-section').forEach(sec => sec.style.display = 'none');
      const targetId = link.getAttribute('data-section');
      document.getElementById(targetId).style.display = 'block';

      // Lazy Loading
      if (targetId === 'adminOverview' && !window.adminStatsLoaded) {
        loadAdminStats();
      }
    });
  });

  // 3. Logout
  document.getElementById('logoutBtn').addEventListener('click', () => {
    SpacexAuth.logout();
  });

  // 4. Initial Payload Loading
  loadAdminStats();

  // 5. Setup Drag and Drop File Upload
  setupUploader();
});

function loadAdminStats() {
  SpacexAPI.getAdminStats()
    .then(res => {
      window.adminStatsLoaded = true;
      const data = res.data;

      const grid = document.getElementById('adminStatsGrid');
      grid.innerHTML = `
        <div class="dash-stat-card">
          <div class="stat-icon">👥</div>
          <div class="stat-value">${data.total_users || 0}</div>
          <div class="stat-label">Total Students</div>
        </div>
        <div class="dash-stat-card">
          <div class="stat-icon">💳</div>
          <div class="stat-value">${data.total_purchases || 0}</div>
          <div class="stat-label">Active Orders</div>
        </div>
        <div class="dash-stat-card">
          <div class="stat-icon" style="color:var(--color-neon);">₹</div>
          <div class="stat-value">${data.total_revenue_formatted || '0'}</div>
          <div class="stat-label">Total Revenue</div>
        </div>
        <div class="dash-stat-card">
          <div class="stat-icon">📈</div>
          <div class="stat-value">${data.monthly_signups || 0}</div>
          <div class="stat-label">New Signups (Month)</div>
        </div>
      `;

      // Populate Table
      const tbody = document.querySelector('#recentPurchasesTable tbody');
      tbody.innerHTML = '';
      if (data.recent_purchases && data.recent_purchases.length > 0) {
        data.recent_purchases.forEach(p => {
          const date = new Date(p.created_at).toLocaleDateString();
          tbody.innerHTML += `
            <tr>
              <td>${date}</td>
              <td>${p.user_name} <br><small style="color:var(--color-text-tertiary);">${p.user_email}</small></td>
              <td>${p.course_title}</td>
              <td style="color:var(--color-neon); font-weight:bold;">${p.currency} ${p.amount}</td>
              <td><span class="badge" style="background:rgba(0,255,136,0.1); border:1px solid rgba(0,255,136,0.2);">Completed</span></td>
            </tr>
          `;
        });
      } else {
        tbody.innerHTML = `<tr><td colspan="5" style="text-align:center;">No recent transactions found.</td></tr>`;
      }
    })
    .catch(err => {
      console.error("Failed to load admin stats:", err);
      showToast("Failed to load admin dashboard statistics", "error");
    });
}

function setupUploader() {
  const dropZone = document.getElementById('dropZone');
  const fileInput = document.getElementById('fileInput');
  const progressBlock = document.getElementById('uploadProgress');
  const progressBar = document.getElementById('uploadProgressBar');
  const progressText = document.getElementById('uploadProgressText');
  const lessonFormSection = document.getElementById('lessonLinkForm');

  // Browse click
  dropZone.addEventListener('click', (e) => {
    if(e.target !== fileInput) fileInput.click();
  });

  // Drag over effects
  dropZone.addEventListener('dragover', (e) => {
    e.preventDefault();
    dropZone.classList.add('dragover');
  });

  dropZone.addEventListener('dragleave', (e) => {
    e.preventDefault();
    dropZone.classList.remove('dragover');
  });

  dropZone.addEventListener('drop', (e) => {
    e.preventDefault();
    dropZone.classList.remove('dragover');
    if (e.dataTransfer.files && e.dataTransfer.files.length > 0) {
      handleUploadFile(e.dataTransfer.files[0]);
    }
  });

  fileInput.addEventListener('change', () => {
    if (fileInput.files.length > 0) {
      handleUploadFile(fileInput.files[0]);
    }
  });

  function handleUploadFile(file) {
    if(!file) return;

    progressBlock.classList.add('active');
    progressBar.style.width = '0%';
    progressText.textContent = `0% Complete (Uploading ${file.name})`;
    
    // Hide form momentarily if they upload again
    lessonFormSection.style.display = 'none';

    const formData = new FormData();
    formData.append('file', file);
    formData.append('action', 'upload');

    // Manually setting up XMLHttpRequest to track progress effectively
    const xhr = new XMLHttpRequest();
    xhr.open('POST', 'backend/api/upload.php', true);
    
    // Attach authorization header manually
    const token = localStorage.getItem('spacex_token');
    if (token) xhr.setRequestHeader('Authorization', `Bearer ${token}`);

    xhr.upload.onprogress = (e) => {
      if (e.lengthComputable) {
        const percent = Math.round((e.loaded / e.total) * 100);
        progressBar.style.width = percent + '%';
        progressText.textContent = `${percent}% Complete - Processing...`;
      }
    };

    xhr.onload = () => {
      if (xhr.status === 201 || xhr.status === 200) {
        const res = JSON.parse(xhr.responseText);
        progressBar.style.width = '100%';
        progressText.textContent = `Upload Successful!`;
        progressText.style.color = 'var(--color-neon)';

        // Open Lesson Link Form
        setTimeout(() => {
          lessonFormSection.style.display = 'block';
          document.getElementById('uploadedFileUrl').value = res.data.file_url;
          document.getElementById('uploadedFilePathDisplay').textContent = "Uploaded Path: " + res.data.file_url;
          showToast('File securely uploaded! Fill the details below to publish lesson.', 'success');
          progressBlock.classList.remove('active');
        }, 1000);
      } else {
        const res = JSON.parse(xhr.responseText || '{}');
        progressText.textContent = `Upload Failed: ${res.error || 'Server error'}`;
        progressText.style.color = '#FF4466';
        progressBar.style.background = '#FF4466';
      }
    };

    xhr.onerror = () => {
      progressText.textContent = `Network Error during upload.`;
      progressText.style.color = '#FF4466';
      progressBar.style.background = '#FF4466';
    };

    xhr.send(formData);
  }

  // Handle Form Submission
  document.getElementById('adminLessonForm').addEventListener('submit', (e) => {
    e.preventDefault();
    
    const payload = {
      course_id: document.getElementById('lessonCourseId').value,
      module_name: document.getElementById('lessonModuleName').value,
      module_order: 1, // default simplification
      title: document.getElementById('lessonTitle').value,
      description: document.getElementById('lessonDesc').value,
      video_url: document.getElementById('uploadedFileUrl').value,
      sort_order: Date.now() // auto sort to bottom
    };

    // Need an addLesson API method — let's manually fetch since it's admin specific
    const token = localStorage.getItem('spacex_token');
    
    fetch('backend/api/admin.php?action=add_lesson', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Authorization': `Bearer ${token}`
      },
      body: JSON.stringify(payload)
    })
    .then(res => res.json())
    .then(data => {
      if(data.success) {
        showToast("Lesson beautifully linked and live on student dashboards!", "success");
        e.target.reset();
        lessonFormSection.style.display = 'none';
      } else {
        showToast(data.error || "Failed to link lesson", "error");
      }
    })
    .catch(err => {
      showToast("Network failure", "error");
    });
  });
}

function showToast(message, type = 'info') {
  const container = document.getElementById('toastContainer');
  if (!container) return alert(message);

  const toast = document.createElement('div');
  toast.className = `spacex-toast toast-${type}`;
  toast.textContent = message;
  
  if (type === 'error') {
    toast.style.background = '#FF4466';
    toast.style.color = '#fff';
    toast.style.padding = '12px 24px';
    toast.style.borderRadius = '8px';
    toast.style.marginBottom = '10px';
    toast.style.opacity = '0';
    toast.style.transition = 'opacity 0.3s ease';
  } else {
    // Existing base CSS definitions for toast
    toast.style.background = 'var(--gradient-neon)';
    toast.style.color = 'var(--color-bg-primary)';
    toast.style.fontWeight = 'bold';
    toast.style.padding = '12px 24px';
    toast.style.borderRadius = '8px';
    toast.style.marginBottom = '10px';
    toast.style.opacity = '0';
    toast.style.transition = 'opacity 0.3s ease';
  }

  container.appendChild(toast);
  setTimeout(() => toast.style.opacity = '1', 10);
  setTimeout(() => {
    toast.style.opacity = '0';
    setTimeout(() => toast.remove(), 300);
  }, 3500);
}
