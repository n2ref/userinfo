var userInfo = {
    getCookie: function (name) {
        var matches = document.cookie.match(new RegExp(
            "(?:^|; )" + name.replace(/([\.$?*|{}\(\)\[\]\\\/\+^])/g, '\\$1') + "=([^;]*)"
        ));
        return matches ? decodeURIComponent(matches[1]) : undefined;
    },


    /**
     *
     * @param name
     * @param value
     * @param options
     */
    setCookie: function (name, value, options) {
        options = options || {};

        var expires = options.expires;

        if (typeof expires === "number" && expires) {
            var d = new Date();
            d.setTime(d.getTime() + expires * 1000);
            expires = options.expires = d;
        }
        if (expires && expires.toUTCString) {
            options.expires = expires.toUTCString();
        }

        value = encodeURIComponent(value);

        var updatedCookie = name + "=" + value;

        for (var propName in options) {
            updatedCookie += "; " + propName;
            var propValue = options[propName];
            if (propValue !== true) {
                updatedCookie += "=" + propValue;
            }
        }

        document.cookie = updatedCookie;
    }
};


if (userInfo.getCookie('screen_size') === undefined) {
    userInfo.setCookie('screen_size', screen.width + 'x' + screen.height);
}
if (userInfo.getCookie('dtz_name') === undefined) {
    userInfo.setCookie('dtz_name', Intl.DateTimeFormat().resolvedOptions().timeZone);
}
if (userInfo.getCookie('dtz_offset') === undefined) {
    var timezone_offset_min = new Date().getTimezoneOffset(),
        offset_hrs = parseInt(Math.abs(timezone_offset_min/60)),
        offset_min = Math.abs(timezone_offset_min%60),
        timezone_standard;

    if (offset_hrs < 10) offset_hrs = '0' + offset_hrs;
    if (offset_min < 10) offset_min = '0' + offset_min;

    if (timezone_offset_min < 0)        timezone_standard = '+' + offset_hrs + ':' + offset_min;
    else if (timezone_offset_min > 0)   timezone_standard = '-' + offset_hrs + ':' + offset_min;
    else if (timezone_offset_min === 0) timezone_standard = '00:00';

    userInfo.setCookie('dtz_offset', timezone_standard);
}