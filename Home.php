<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>Home</title>
</head>
<body>

<div id="main">
	
	<div class="titleBar">
		<h1 class="centered">Checkers</h1>
		<h4 class="centered">Play Checkers with friends</h4>
	</div>
	
	<div class="registrationBar">
		<h3>Register/Login to start a game</h3> 
		<p>Username: <input type="text" class="username" id="username"></p>
		<p>Password: <input type="password" class="password" id="password"></p>
		<div class="buttons">
			<input type="button" value="register" onclick="submitRegistrationCredentials(); return false">
			<input type="button" value="login" onclick="submitLoginCredentials(); return false">
		</div>
		<p id="messages"></p>
	</div>
</div>
	

</body>

<script>
	function submitRegistrationCredentials(){
		//alert("called");
		var username = document.getElementById("username").value;
		var password = document.getElementById("password").value;
		//alert(username);
		//alert(password);
	    var ajax = new XMLHttpRequest();
	    ajax.open("POST", "controller.php", true);
	    ajax.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
	    ajax.send("username=" + username + "&password=" + password + "&method=registration");
	    ajax.onreadystatechange = function() {
	        if (ajax.readyState == 4 && ajax.status == 200) {
				alert(ajax.responseText);
	        	document.getElementById("messages").innerHTML = ajax.responseText;
	        }
	    };
	}
	
	function submitLoginCredentials(){
		var username = document.getElementById("username").value;
		var password = document.getElementById("password").value;
	    var ajax = new XMLHttpRequest();
	    ajax.open("POST", "controller.php", true);
	    ajax.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
	    ajax.send("username=" + username + "&password=" + password + "&method=login");
	    ajax.onreadystatechange = function() {
	        if (ajax.readyState == 4 && ajax.status == 200){
	        	if (ajax.responseText == true){
					window.location = "gamefinder.php";
	        	}
	        	else{
	        		document.getElementById("messages").innerHTML = "username and password do not match";
	        	}
	        }
	    };
	}
</script>

</html>