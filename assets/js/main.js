/**
 * Wedding Management System - Main JavaScript File
 * Contains common functions and utilities for all user roles
 */

$(document).ready(function() {
    // Initialize common components
    initializeComponents();
    
    // Auto-hide alerts after 5 seconds
    setTimeout(function() {
        $('.alert').fadeOut('slow');
    }, 5000);
    
    // Initialize tooltips if Bootstrap is available
    if (typeof $().tooltip === 'function') {
        $('[data-toggle="tooltip"]').tooltip();
    }
    
    // Initialize popovers if Bootstrap is available
    if (typeof $().popover === 'function') {
        $('[data-toggle="popover"]').popover();
    }
    
    // Smooth scrolling for anchor links
    $('a[href^="#"]').on('click', function(e) {
        e.preventDefault();
        const target = $(this.getAttribute('href'));
        if (target.length) {
            $('html, body').stop().animate({
                scrollTop: target.offset().top - 70
            }, 1000);
        }
    });
    
    // Form validation enhancement
    $('form').on('submit', function(e) {
        const form = $(this);
        const requiredFields = form.find('[required]');
        let isValid = true;
        
        requiredFields.each(function() {
            const field = $(this);
            if (!field.val().trim()) {
                field.addClass('is-invalid');
                isValid = false;
            } else {
                field.removeClass('is-invalid').addClass('is-valid');
            }
        });
        
        if (!isValid) {
            e.preventDefault();
            showAlert('Please fill in all required fields.', 'error');
        }
    });
    
    // Real-time field validation
    $('[required]').on('blur', function() {
        const field = $(this);
        if (!field.val().trim()) {
            field.addClass('is-invalid');
        } else {
            field.removeClass('is-invalid').addClass('is-valid');
        }
    });
    
    // Email validation
    $('input[type="email"]').on('blur', function() {
        const email = $(this).val();
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        
        if (email && !emailRegex.test(email)) {
            $(this).addClass('is-invalid');
            showFieldError($(this), 'Please enter a valid email address');
        } else if (email) {
            $(this).removeClass('is-invalid').addClass('is-valid');
            hideFieldError($(this));
        }
    });
    
    // Phone validation
    $('input[type="tel"]').on('blur', function() {
        const phone = $(this).val();
        const phoneRegex = /^[\+]?[0-9\s\-\(\)]{10,}$/;
        
        if (phone && !phoneRegex.test(phone)) {
            $(this).addClass('is-invalid');
            showFieldError($(this), 'Please enter a valid phone number');
        } else if (phone) {
            $(this).removeClass('is-invalid').addClass('is-valid');
            hideFieldError($(this));
        }
    });
});

function initializeComponents() {
    // Initialize DataTables
    if ($.fn.DataTable && $('.data-table').length) {
        $('.data-table').DataTable({
            responsive: true,
            lengthChange: true,
            autoWidth: false,
            pageLength: 25,
            order: [[0, 'desc']], // Default sort by first column descending
            language: {
                search: "Search:",
                lengthMenu: "Show _MENU_ entries",
                info: "Showing _START_ to _END_ of _TOTAL_ entries",
                infoEmpty: "Showing 0 to 0 of 0 entries",
                infoFiltered: "(filtered from _TOTAL_ total entries)",
                zeroRecords: "No matching records found",
                emptyTable: "No data available in table",
                paginate: {
                    first: "First",
                    last: "Last",
                    next: "Next",
                    previous: "Previous"
                }
            },
            columnDefs: [
                { orderable: false, targets: 'no-sort' }
            ]
        });
    }
    
    // Initialize Select2 if available
    if ($.fn.select2 && $('.select2').length) {
        $('.select2').select2({
            theme: 'bootstrap4',
            width: '100%'
        });
    }
    
    // Initialize date pickers if available
    if ($.fn.datepicker && $('.datepicker').length) {
        $('.datepicker').datepicker({
            format: 'yyyy-mm-dd',
            autoclose: true,
            todayHighlight: true
        });
    }
    
    // Initialize time pickers if available
    if ($.fn.timepicker && $('.timepicker').length) {
        $('.timepicker').timepicker({
            format: 'HH:MM',
            autoclose: true
        });
    }
}

// Utility Functions
function showAlert(message, type = 'info', duration = 5000) {
    const alertClass = type === 'error' ? 'danger' : type;
    const alertHtml = `
        <div class="alert alert-${alertClass} alert-dismissible fade show" role="alert">
            ${message}
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
    `;
    
    // Remove existing alerts
    $('.alert').remove();
    
    // Add new alert to the top of the content
    $('.content-wrapper .content').prepend(alertHtml);
    
    // Auto-hide after duration
    if (duration > 0) {
        setTimeout(function() {
            $('.alert').fadeOut('slow');
        }, duration);
    }
}

function showFieldError(field, message) {
    hideFieldError(field);
    field.after(`<div class="invalid-feedback d-block">${message}</div>`);
}

function hideFieldError(field) {
    field.next('.invalid-feedback').remove();
}

function confirmAction(message, callback, title = 'Are you sure?') {
    if (typeof Swal !== 'undefined') {
        Swal.fire({
            title: title,
            text: message,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Yes, proceed',
            cancelButtonText: 'Cancel'
        }).then((result) => {
            if (result.isConfirmed) {
                callback();
            }
        });
    } else {
        if (confirm(message)) {
            callback();
        }
    }
}

function formatCurrency(amount, currency = 'RM') {
    return currency + ' ' + parseFloat(amount).toLocaleString('en-US', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    });
}

function formatDate(dateString, format = 'long') {
    const date = new Date(dateString);
    
    if (format === 'short') {
        return date.toLocaleDateString('en-US', {
            year: 'numeric',
            month: 'short',
            day: 'numeric'
        });
    } else if (format === 'long') {
        return date.toLocaleDateString('en-US', {
            weekday: 'long',
            year: 'numeric',
            month: 'long',
            day: 'numeric'
        });
    } else {
        return date.toLocaleDateString();
    }
}

function formatTime(timeString) {
    const time = new Date('2000-01-01 ' + timeString);
    return time.toLocaleTimeString('en-US', {
        hour: 'numeric',
        minute: '2-digit',
        hour12: true
    });
}

function timeAgo(timestamp) {
    const now = new Date();
    const date = new Date(timestamp);
    const diffInSeconds = Math.floor((now - date) / 1000);
    
    if (diffInSeconds < 60) return 'just now';
    if (diffInSeconds < 3600) return Math.floor(diffInSeconds / 60) + ' minutes ago';
    if (diffInSeconds < 86400) return Math.floor(diffInSeconds / 3600) + ' hours ago';
    if (diffInSeconds < 2592000) return Math.floor(diffInSeconds / 86400) + ' days ago';
    
    return date.toLocaleDateString();
}

function loadingState(element, isLoading = true) {
    if (isLoading) {
        element.data('original-text', element.html());
        element.html('<i class="fas fa-spinner fa-spin"></i> Loading...').prop('disabled', true);
    } else {
        element.html(element.data('original-text')).prop('disabled', false);
    }
}

function copyToClipboard(text) {
    if (navigator.clipboard) {
        navigator.clipboard.writeText(text).then(() => {
            showAlert('Copied to clipboard!', 'success', 2000);
        });
    } else {
        // Fallback for older browsers
        const textArea = document.createElement('textarea');
        textArea.value = text;
        document.body.appendChild(textArea);
        textArea.select();
        document.execCommand('copy');
        document.body.removeChild(textArea);
        showAlert('Copied to clipboard!', 'success', 2000);
    }
}

// AJAX helper function
function makeAjaxRequest(url, data, callback, method = 'POST') {
    $.ajax({
        url: url,
        type: method,
        data: data,
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                callback(response);
            } else {
                showAlert(response.message || 'An error occurred', 'error');
            }
        },
        error: function(xhr, status, error) {
            showAlert('Network error: ' + error, 'error');
        }
    });
}

// File upload helper
function initializeFileUpload(selector, options = {}) {
    const defaultOptions = {
        maxFileSize: 5 * 1024 * 1024, // 5MB
        allowedTypes: ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'],
        multiple: false
    };
    
    const settings = Object.assign(defaultOptions, options);
    
    $(selector).on('change', function(e) {
        const files = e.target.files;
        const validFiles = [];
        
        for (let i = 0; i < files.length; i++) {
            const file = files[i];
            
            // Check file size
            if (file.size > settings.maxFileSize) {
                showAlert(`File "${file.name}" is too large. Maximum size is ${settings.maxFileSize / (1024 * 1024)}MB.`, 'error');
                continue;
            }
            
            // Check file type
            if (!settings.allowedTypes.includes(file.type)) {
                showAlert(`File "${file.name}" has invalid type. Allowed types: ${settings.allowedTypes.join(', ')}.`, 'error');
                continue;
            }
            
            validFiles.push(file);
        }
        
        if (validFiles.length !== files.length) {
            // Reset the input to remove invalid files
            $(this).val('');
        }
        
        return validFiles.length > 0;
    });
}

// Print functionality
function printElement(selector) {
    const element = $(selector);
    if (element.length) {
        const printWindow = window.open('', '_blank');
        printWindow.document.write(`
            <html>
                <head>
                    <title>Print</title>
                    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">
                    <style>
                        @media print {
                            .no-print { display: none !important; }
                            body { font-size: 12px; }
                        }
                    </style>
                </head>
                <body>
                    ${element.html()}
                </body>
            </html>
        `);
        printWindow.document.close();
        printWindow.focus();
        printWindow.print();
        printWindow.close();
    }
}

// Export table to CSV
function exportTableToCSV(tableSelector, filename = 'export.csv') {
    const table = $(tableSelector);
    if (!table.length) return;
    
    const csv = [];
    const rows = table.find('tr');
    
    rows.each(function() {
        const row = [];
        $(this).find('th, td').each(function() {
            let text = $(this).text().trim();
            // Escape quotes and wrap in quotes if contains comma
            if (text.includes(',') || text.includes('"')) {
                text = '"' + text.replace(/"/g, '""') + '"';
            }
            row.push(text);
        });
        csv.push(row.join(','));
    });
    
    const csvContent = csv.join('\n');
    const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
    const link = document.createElement('a');
    
    if (navigator.msSaveBlob) { // IE 10+
        navigator.msSaveBlob(blob, filename);
    } else {
        link.href = URL.createObjectURL(blob);
        link.download = filename;
        link.style.visibility = 'hidden';
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
    }
}

// Global logout function
function logout() {
    confirmAction(
        'Are you sure you want to logout?',
        function() {
            window.location.href = '../includes/logout.php';
        },
        'Logout Confirmation'
    );
}

// Dark mode toggle (if implemented)
function toggleDarkMode() {
    $('body').toggleClass('dark-mode');
    const isDark = $('body').hasClass('dark-mode');
    localStorage.setItem('darkMode', isDark);
}

// Initialize dark mode from localStorage
$(document).ready(function() {
    if (localStorage.getItem('darkMode') === 'true') {
        $('body').addClass('dark-mode');
    }
});

// Auto-save form data to localStorage
function enableAutoSave(formSelector, key) {
    const form = $(formSelector);
    
    // Load saved data
    const savedData = localStorage.getItem(key);
    if (savedData) {
        const data = JSON.parse(savedData);
        Object.keys(data).forEach(name => {
            form.find(`[name="${name}"]`).val(data[name]);
        });
    }
    
    // Save data on change
    form.on('change input', function() {
        const formData = {};
        form.find('input, select, textarea').each(function() {
            if ($(this).attr('name')) {
                formData[$(this).attr('name')] = $(this).val();
            }
        });
        localStorage.setItem(key, JSON.stringify(formData));
    });
    
    // Clear saved data on successful submit
    form.on('submit', function() {
        setTimeout(() => {
            if (!form.find('.is-invalid').length) {
                localStorage.removeItem(key);
            }
        }, 1000);
    });
}

// Initialize notification system
function initializeNotifications() {
    // Check for new notifications every 5 minutes
    setInterval(function() {
        if (typeof checkForNotifications === 'function') {
            checkForNotifications();
        }
    }, 300000);
}

// Start notification checking when document is ready
$(document).ready(function() {
    initializeNotifications();
});

// Global error handler
window.addEventListener('error', function(e) {
    console.error('Global error:', e.error);
    // Log error to server if needed
});

// Global unhandled promise rejection handler
window.addEventListener('unhandledrejection', function(e) {
    console.error('Unhandled promise rejection:', e.reason);
    // Log error to server if needed
});