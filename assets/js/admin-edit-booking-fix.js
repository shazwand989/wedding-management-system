/**
 * 🔧 Admin Edit Booking Fix - JavaScript Navigation Solution
 * 
 * This file provides a JavaScript-based solution for the edit booking redirect issue.
 * Instead of relying on HTML links, we use JavaScript to ensure proper navigation.
 */

// Function to navigate to edit booking page
function editBooking(bookingId) {
    console.log('Edit booking clicked for ID:', bookingId);
    
    // Validate booking ID
    if (!bookingId || bookingId <= 0) {
        alert('Invalid booking ID: ' + bookingId);
        return false;
    }
    
    // Create the edit URL
    const editUrl = 'edit_booking.php?id=' + bookingId;
    console.log('Navigating to:', editUrl);
    
    // Navigate to edit page
    try {
        window.location.href = editUrl;
    } catch (error) {
        console.error('Navigation error:', error);
        alert('Error navigating to edit page: ' + error.message);
    }
    
    return false; // Prevent any default action
}

// Alternative: Open in new tab
function editBookingNewTab(bookingId) {
    if (!bookingId || bookingId <= 0) {
        alert('Invalid booking ID: ' + bookingId);
        return false;
    }
    
    const editUrl = 'edit_booking.php?id=' + bookingId;
    window.open(editUrl, '_blank');
    return false;
}

// Enhanced version with error handling and feedback
function editBookingEnhanced(bookingId) {
    // Show loading state
    const button = event.target.closest('.btn');
    const originalText = button.innerHTML;
    button.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Loading...';
    button.disabled = true;
    
    // Validate booking ID
    if (!bookingId || bookingId <= 0) {
        alert('Invalid booking ID: ' + bookingId);
        button.innerHTML = originalText;
        button.disabled = false;
        return false;
    }
    
    // Add small delay to show feedback
    setTimeout(() => {
        const editUrl = 'edit_booking.php?id=' + bookingId;
        console.log('Navigating to edit booking:', editUrl);
        window.location.href = editUrl;
    }, 300);
    
    return false;
}

// Fix existing edit buttons on page load
document.addEventListener('DOMContentLoaded', function() {
    console.log('🔧 Edit booking fix loaded');
    
    // Find all edit booking links and add click handlers
    const editLinks = document.querySelectorAll('a[href*="edit_booking.php"]');
    
    editLinks.forEach(function(link) {
        // Extract booking ID from href
        const href = link.getAttribute('href');
        const matches = href.match(/id=(\d+)/);
        
        if (matches && matches[1]) {
            const bookingId = matches[1];
            
            // Replace link click with JavaScript function
            link.addEventListener('click', function(e) {
                e.preventDefault();
                console.log('Edit link clicked, using JS navigation for ID:', bookingId);
                editBookingEnhanced(bookingId);
            });
            
            // Add visual indicator that this is using JS
            link.setAttribute('title', 'Edit Booking #' + bookingId + ' (JS Enhanced)');
            link.style.position = 'relative';
        }
    });
    
    console.log('Enhanced', editLinks.length, 'edit booking links');
});

// Debug function to test edit functionality
function testEditFunction(bookingId) {
    console.log('🧪 Testing edit function for booking ID:', bookingId);
    
    // Test different navigation methods
    const methods = {
        'location.href': () => window.location.href = 'edit_booking.php?id=' + bookingId,
        'location.assign': () => window.location.assign('edit_booking.php?id=' + bookingId),
        'window.open': () => window.open('edit_booking.php?id=' + bookingId, '_self'),
        'form submit': () => {
            const form = document.createElement('form');
            form.method = 'GET';
            form.action = 'edit_booking.php';
            
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'id';
            input.value = bookingId;
            
            form.appendChild(input);
            document.body.appendChild(form);
            form.submit();
        }
    };
    
    const method = prompt('Choose navigation method:\n1. location.href\n2. location.assign\n3. window.open\n4. form submit', '1');
    
    switch(method) {
        case '1': methods['location.href'](); break;
        case '2': methods['location.assign'](); break;
        case '3': methods['window.open'](); break;
        case '4': methods['form submit'](); break;
        default: methods['location.href'](); break;
    }
}