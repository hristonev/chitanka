function showNewForm(link)
{
	$(link).addClass("loading");

	$.get(link.href, function(data){
		$(link).before(data).removeClass("loading").prev("form").find(":input:visible:eq(0)").focus();
	});
}

function showEditForm(link)
{
	$(link).addClass("loading");

	var match = link.href.match(/edit\/(.+)/);
	if (match === null) {
		alert(link.href + " is an invalid edit link.");

		return;
	}

	$.get(link.href, function(data){
		var sel = $("#"+match[1].replace(/\//g, "_"));
		$(sel).hide().after(data).next("form").find(":input:visible:eq(0)").focus();
		$(link).removeClass("loading");
	});
}


function submitForm(form)
{
	var $form = $(form);
	var $button = $(":submit", $form).addClass("loading");
	$.post(form.action, $form.serialize(), function(response){
		$button.removeClass("loading").blur();
		if (typeof response == "string") {
			$form.replaceWith(response);
		} else {
			if (response.addClass) {
				$button.addClass(response.addClass);
			} else if (response.removeClass) {
				$button.removeClass(response.removeClass);
			}
			if (response.setTitle) {
				$button.attr("title", response.setTitle);
			}
		}
	}, "json");
}

function submitNewForm(form)
{
	var $form = $(form);
	$(":submit", $form).addClass("loading");
	$.post(form.action, $form.serialize(), function(data){
		$form.prev().append(data).end().remove();
	});
}

function submitEditForm(form)
{
	var $form = $(form);
	$(":submit", $form).addClass("loading");
	$.post(form.action, $form.serialize(), function(data){
		$form.prev().html(data).show().end().remove();
	});
}

function submitDeleteForm(form)
{
	var $form = $(form);
	$form.find(":submit").addClass("loading");
	$.ajax({
		url: form.action,
		type: "DELETE",
		data: $form.serialize(),
	}).done(function(data) {
		var $parent = $form.closest(".deletable");
		if ( ! $parent.length) {
			$parent = $form.parent();
		}
		$parent.remove();
	});
}


function enhanceModifying()
{
	$("body")
		.delegate("a.action-new", "click", function(){
			if (user.canTakeAction(this)) {
				showNewForm(this);
			} else {
				alert("Нямате необходимите права за това действие.");
			}

			return false;
		})
		.delegate("a.action-edit", "click", function(){
			if (user.canTakeAction(this)) {
				showEditForm(this);
			} else {
				alert("Нямате необходимите права за това действие.");
			}

			return false;
		})
		.delegate("a[data-edit]", "click", function(event){
			if (event.altKey || event.shiftKey) {
				window.open($(this).data("edit"));
				return false;
			}
			return true;
		})
		.delegate("form.new-form", "submit", function(){
			if (user.canTakeAction(this)) {
				submitNewForm(this);
			} else {
				alert("Нямате необходимите права за това действие.");
			}

			return false;
		})
		.delegate("form.edit-form", "submit", function(){
			if (user.canTakeAction(this)) {
				submitEditForm(this);
			} else {
				alert("Нямате необходимите права за това действие.");
			}

			return false;
		})
		.delegate("form.delete-form", "submit", function(){
			if (user.canTakeAction(this)) {
				if (confirm("Наистина ли да се изтрие? Връщане назад няма.")) {
					submitDeleteForm(this);
				}
			} else {
				alert("Нямате необходимите права за това действие.");
			}

			return false;
		})
		.delegate("form.action-form", "submit", function(){
			if (user.canTakeAction(this)) {
				submitForm(this);
			} else {
				alert("Нямате необходимите права за това действие.");
			}

			return false;
		})
		.delegate("button.cancel", "click", function(){
			$(this.form).prev().show().end().remove();
		});

}


function showBookmarks()
{
	var $texts = $(".text-entity");
	if ($texts.length) {
		var textIds = $texts.map(function(){ return $(this).data("id") }).get().join(",");
		$.post(_GLOBALS.scriptname + "user-special-texts", {texts: textIds}, function(response){
			$texts.each(function(){
				var id = $(this).data("id");
				if (typeof response.read[id] == "number") {
					$("a.textlink", this).addClass("read");
				}
				if (typeof response.favorities[id] == "number") {
					var newText = "Премахване от Избрани";
					$("form.bookmark-form button", this)
						.addClass("active")
						.attr("title", newText);
;
				}
			});
		}, "json");
	}
}


function initCluetip()
{
	$("a.booklink")
		.live("click", function(){
			// do not spam users if they wish to follow a link
			_GLOBALS.showCluetip = false;
		})
		.cluetip({
			width: ($(document).width() / 1.25),
			positionBy: 'mouse',
			//splitTitle: ';',
			sticky: true,
			dropShadow: false,
			closePosition: 'title',
			closeText: '<span>Затваряне</span>',
			mouseOutClose: true,
			hoverIntent: {
				sensitivity: 10,
				interval:    500 // in milliseconds
			},
			onActivate: function(e) {
				return _GLOBALS.showCluetip;
			}
		});
}

var togglerCookieName = "nomenu";
// create a menu toggle link
(function(){
	if ($("#nav-main").length == 0) {
		return;
	}
	var menuToggler = {
		hide: function() {
			this.init();
			$(this.navEl).hide();
			$(this.contEl).css(this.navPos, this.margin.n);
			$.cookie(togglerCookieName, 1, {path: '/'});
		},
		show: function() {
			$(this.navEl).show();
			$(this.contEl).css(this.navPos, this.margin.o);
			$.cookie(togglerCookieName, null, {path: '/'});
		},
		navEl: "#navigation",
		contEl: "#main-content",
		navPos: "",
		margin: {},
		init: function() {
			if ( this.navPos != "" ) {
				return;
			}
			var c = $(this.contEl);
			var l = c.css("margin-left");
			var r = c.css("margin-right");
			if ( r > l ) {
				// expand to the right
				this.save("right", r, l);
			} else {
				// expand to the left
				this.save("left", l, r);
			}
		},
		save: function(pos, ov, nv) {
			this.navPos = "margin-" + pos;
			this.margin = {o: ov, n: nv};
		}
	};
	var hide = "Без меню";
	var show = "С меню";
	$('<a id="toggle-nav-link" href="#"></a>')
		.text(hide)
		.toggle(function(){
			menuToggler.hide();
			$(this).text(show);
			return false;
		}, function(){
			menuToggler.show();
			$(this).text(hide);
			return false;
		})
		.appendTo("#content-wrapper");
})();


$(function(){
	if (user.isAuthenticated()) {
		showBookmarks();
	}

	initCluetip();
	$(".tooltip").tooltip({showURL: false, showBody: "<br>"});

	if ($.cookie(togglerCookieName)) {
		$("#toggle-nav-link").click();
	}

	enhanceModifying();

	$("#toc").boxcollapse();
});


/** Set new active style sheet */
function setActiveStyleSheet(styleSheet) {
	$("#activeStyleSheet").attr("href", styleSheet);
}


/**
	Initialise table sorter on all tables marked as sortable.
	@param table Optional table selector
*/
function initTableSorter(options) {
	if ( ! $.tablesorter ) {
		return;
	}
	$(document).ready(function() {
		$.tablesorter.defaults.widgets = ['zebra'];
		$.tablesorter.defaults.decimal = [','];
		// TODO sorting of KiB
		$("table.sortable").tablesorter(options);
	});
}

/**
	@global postform
	@param cid Comment ID
*/
function initReply(cid) {
	var target = "#postform";
	var postform = $(target);
	if ( ! postform.length ) {
		return true;
	}
	postform.show();
	postform[0].replyto.value = cid;
	if (cid > 0) {
		target = "#replyto" + cid;
		$(target).append(postform);
	}
	location.href = target;
	return false;
}


function getTextIdFromLink( link ) {
	var matches = $(link).attr("class").match(/text-(\d+)/);
	if ( ! matches ) {
		return false;
	}
	return matches[1];
}

/** Checks if a value exists in an array */
function inArray( val, arr ) {
	return jQuery.inArray( val, arr ) != -1;
}


/*
	Miscellaneous AJAX functions
*/

/**
* Rate a text
* @param form ID of the text being rated
*/
function saveRating(form) {
	if ( ! user.isAuthenticated()) {
		alert("Само регистрирани потребители могат да дават оценки.");

		return false;
	}
	$form = $(form);
	$(".indicator", $form).addClass("loading");
	return ! $.post(form.action, $form.serialize(), function(newForm){
		$form.replaceWith(newForm);
	});
}


/** Log-in form */
var showLoginForm = (function() {
	var isShown = false;
	var responseHandler = null;

	return (function( anchor ) {
		if ( isShown ) {
			return false;
		}

		if ( ! responseHandler ) {
			responseHandler = function( response ) {
				$.event.trigger("ajaxStop");
				$("<div/>")
					.addClass("ajax-response-top")
					.append(response)
					.prependTo("body");
				isShown = true;
			};
		}

		return ! $.mypost(
			"login.makeLoginForm",
			{"returnto" : location.href},
			responseHandler);
	});
})();


/** Comment preview */
var makePreview = (function() {
	var box = null;
	var responseHandler = null;

	return (function( form, comment, reader ) {
		if ( ! box ) {
			box = $("<div/>").attr("id", "previewed").insertAfter(form);
			responseHandler = function( response ) {
				$.event.trigger("ajaxStop");
				box.empty().html(response);
				location.href = "#previewed";
			};
		}

		return ! $.mypost(
			"makePreview",
			{"commenttext" : comment, "reader" : reader},
			responseHandler);
	});
})();


var user = {
	isAuthenticated: function()
	{
		return $(".user-profile").length > 0;
	},

	canTakeAction: function(handle)
	{
		var credentials = $(handle).data("credentials");
		if ( ! credentials) {
			return this.isAuthenticated();
		}

		return $.inArray(credentials, userGroups) != -1;
	}
};
