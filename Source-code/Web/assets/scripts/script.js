// TOAST
document.addEventListener('DOMContentLoaded', function () {
    // khởi tạo toast
    var toastElList = [].slice.call(document.querySelectorAll('.toast'));
    var toastList = toastElList.map(function (toastEl) {
        return new bootstrap.Toast(toastEl, {
            autohide: true,
            delay: 3000,
            animation: true
        });
    });
    
    // hiển thị toast
    toastList.forEach(function (toast) {
        toast.show();
    });
});

       // kích hoạt tooltips
       var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
       var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
           return new bootstrap.Tooltip(tooltipTriggerEl)
       })
       
       // kích hoạt popovers
       var popoverTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="popover"]'))
       var popoverList = popoverTriggerList.map(function (popoverTriggerEl) {
           return new bootstrap.Popover(popoverTriggerEl)
       })
       
       // tự động ẩn alerts sau 5 giây
       window.setTimeout(function() {
           document.querySelectorAll('.alert:not(.alert-permanent)').forEach(function(alert) {
               var bsAlert = new bootstrap.Alert(alert);
               bsAlert.close();
           });
       }, 5000);
       
       // thêm hiệu ứng hover cho các liên kết
       document.addEventListener('DOMContentLoaded', function() {
           // thêm hiệu ứng hover cho các liên kết
           document.querySelectorAll('.hover-link').forEach(function(link) {
               link.addEventListener('mouseover', function() {
                   this.style.color = '#3b82f6';
                   this.style.transform = 'translateX(3px)';
                   this.style.transition = 'all 0.2s ease';
               });
               link.addEventListener('mouseout', function() {
                   this.style.color = '';
                   this.style.transform = '';
               });
           });
           
           // thêm hiệu ứng hover cho các icon
           document.querySelectorAll('.hover-primary').forEach(function(el) {
               el.addEventListener('mouseover', function() {
                   this.style.color = '#3b82f6';
                   this.style.transform = 'scale(1.2)';
                   this.style.transition = 'all 0.2s ease';
               });
               el.addEventListener('mouseout', function() {
                   this.style.color = '';
                   this.style.transform = '';
               });
           });
       });