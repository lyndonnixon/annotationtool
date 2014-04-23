/**
*       @description stripslashes function
*       @author Trey Shugart
*       @version 1.0.0
*       @date 2008-05-07
*       @license GNU LGPL (http://www.gnu.org/licenses/lgpl.html)
*/
(function($) {
        $.stripslashes = function (str) {
                str = str.replace(/\\'/g,'\'');
                str = str.replace(/\\"/g,'"');
                str = str.replace(/\\\\/g,'\\');
                str = str.replace(/\\0/g,'\0');
                return str;
        };
})(jQuery);