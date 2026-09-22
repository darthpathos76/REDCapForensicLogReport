/* ForeTELL Lite — dashboard.js */
(function () {
    'use strict';

    document.addEventListener('DOMContentLoaded', function () {
        var alerts = document.querySelectorAll('.ftl-auto-dismiss');
        alerts.forEach(function (el) {
            setTimeout(function () {
                el.style.transition = 'opacity .4s';
                el.style.opacity = '0';
                setTimeout(function () {
                    if (el.parentNode) {
                        el.parentNode.removeChild(el);
                    }
                }, 400);
            }, 8000);
        });
    });
}());
