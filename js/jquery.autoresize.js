(function($) {
	$.fn.autoresize = function(option) {
		var settings = $.extend({
			speed: 0,
			padding: 2
		}, option);

		$(this).each(function() {
			var default_height = parseInt($(this).css('height'));
			var default_rows   = parseInt($(this).attr('rows'));
			var line_height    = Math.floor(default_height / default_rows);

			$.fn.autoresize.resize($(this), default_height, line_height, settings.padding, 0);

			$(this).keyup(function() {
				$.fn.autoresize.resize($(this), default_height, line_height, settings.padding, settings.speed);
			});
		});

		return this;
	};

	$.fn.autoresize.resize = function(target, default_height, line_height, padding, speed) {
		var text = target.val();

		text = text.replace(/\r?\n/g, '\r');
		text = text.replace(/\r/g, '\n');

		var n    = text.match(/\n/g);
		var rows = n ? n.length + padding : padding;

		var height = rows * line_height;

		if (height > default_height) {
			if (speed) {
				target.animate({
					height: height
				}, speed);
			} else {
				target.css('height', height);
			}
		}
	};
})(jQuery);
