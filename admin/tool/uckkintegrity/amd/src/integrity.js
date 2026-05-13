define(['core/notification'], function(Notification) {
    return {
        init: function(selector) {
            var root = document.querySelector(selector);
            if (!root) {
                return;
            }
            root.addEventListener('click', function(e) {
                var target = e.target.closest('[data-confirm]');
                if (!target) {
                    return;
                }
                e.preventDefault();
                Notification.confirm(target.getAttribute('data-confirm'), '', 'OK', 'Cancel', function() {
                    window.location.href = target.href;
                });
            });
        }
    };
});