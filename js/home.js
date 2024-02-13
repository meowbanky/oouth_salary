// JavaScript Document

$(function () {
	$('.error').hide();

});



function doLogin() {
	var username = $('#username').val();
	var password = $('#password').val();
	if (username == "") {
		$('.error').text('You are Required to enter your Username ');
		$('.error').animate({ height: "show", opacity: "show" });
		setTimeout(function () { $('.error').animate({ height: "hide", opacity: "hide" }); $('.error').text(''); }, 3000);
		$('#username').focus();
	} else if (password == "") {
		$('.error').text('You are Required to enter your Password ');
		$('.error').animate({ height: "show", opacity: "show" });
		setTimeout(function () { $('.error').animate({ height: "hide", opacity: "hide" }); $('.error').text(''); }, 3000);
		$('#password').focus();
	}
	else {

		// $.ajax({
		// 	type: "POST",
		// 	url: "classes/controller.php?act=login",
		// 	data: { username: username, password: password, location: location },
		// 	dataType: 'json', // what type of data do we expect back from the server
		// 	encode: true,
		// 	xhrFields: {
		// 		onprogress: function (e) {
		// 			$('#id').val('loginin...');

		// 		}
		// 	},
		// 	success: function (data) { doCheck(data); }


		// });

		$('#loginform').ajaxSubmit({
			url: 'classes/controller.php?act=login',
			data: { username: username, password: password },
			dataType: 'json', // what type of data do we expect back from the server
			// 	encode: true,
			xhrFields: {
				onprogress: function (e) {

					$('#submit').val('Checking to login...')
				}
			},
			success: function (data, message) {
				doCheck(data);

			}
		})
	}
}

function doLoginExpire() {
	var username = $('#username').val();
	var password = $('#password').val();
	var location = $('#location').val();
	if (username == "") {
		$('.error').text('You are Required to enter your Username ');
		$('.error').animate({ height: "show", opacity: "show" });
		setTimeout(function () { $('.error').animate({ height: "hide", opacity: "hide" }); $('.error').text(''); }, 3000);
		$('#username').focus();
		$('#submit').val('Login')
	} else if (password == "") {
		$('.error').text('You are Required to enter your Password ');
		$('.error').animate({ height: "show", opacity: "show" });
		setTimeout(function () { $('.error').animate({ height: "hide", opacity: "hide" }); $('.error').text(''); }, 3000);
		$('#password').focus();
		$('#submit').val('Login')
	}
	else {

		$.ajax({
			type: "POST",
			url: "login2.php",
			data: { username: username, password: password, location: location },
			dataType: 'json', // what type of data do we expect back from the server
			encode: true,
			success: function (data) { doCheck1(data); }


		});
	}
}

function doCheck1(val) {
	//console.log(val.success);
	if (val.success == 'true') {
		//email = document.getElementById('username').value;
		//window.location = 'home.php';
		location.reload();
	}
	else if (val.success == "false") {
		$('.error').text('Invalid Username OR Password');
		$('.error').animate({ height: "show", opacity: "show" });
		setTimeout(function () { $('.error').animate({ height: "hide", opacity: "hide" }); $('.error').text(''); }, 3000);
	}
}

function doCheck(val) {
	console.log(val);
	if (val.success == 'true') {
		//email = document.getElementById('username').value;
		window.location = 'home.php';
	}
	else if (val.success == "false") {
		$('.error').text('Invalid Username OR Password');
		$('.error').animate({ height: "show", opacity: "show" });
		setTimeout(function () { $('.error').animate({ height: "hide", opacity: "hide" }); $('.error').text(''); }, 3000);
		$('#submit').val('Login')
	}
}

function checkit(id_suffix, count, cand_id, office_id, matric, type) {
	//alert(type);
	for (var x = 1; x <= count; x++) {
		document.getElementById('name' + x).src = "images/unchecked.png";
	}

	document.getElementById('name' + id_suffix).src = "images/checked.png";
	//	alert(matric);
	doVote(cand_id, office_id, matric, type);
}

function doVote(cand_id, office_id, matric, type) {
	$.ajax({
		type: "POST",
		url: "insert.php",
		data: { cand_id: cand_id, office_id: office_id, matric: matric, type: type },
		success: function (data) {
			//docheck();
		}
	});

}
