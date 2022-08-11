<?php

// Check for empty input signup
function emptyInputSignup($username, $email, $pwd, $pwdRepeat) {
	if (empty($username) || empty($email) || empty($pwd) || empty($pwdRepeat)) {
		$result = true;
	}
	else {
		$result = false;
	}
	return $result;
}

// Check invalid username
function invalidUid($username) {
	if (!preg_match("/^[a-zA-Z0-9]*$/", $username)) {
		$result = true;
	}
	else {
		$result = false;
	}
	return $result;
}

// Check invalid email
function invalidEmail($email) {
	if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
		$result = true;
	}
	else {
		$result = false;
	}
	return $result;
}

// Check if passwords matches
function pwdMatch($pwd, $pwdrepeat) {
	if ($pwd !== $pwdrepeat) {
		$result = true;
	}
	else {
		$result = false;
	}
	return $result;
}

// Check if username is in database, if so then return data
function uidExists($conn, $username) {
	$conn = GetDBConnection();
  $sql = "SELECT * FROM users WHERE usersUid = ? OR usersEmail = ?;";
	$stmt = mysqli_stmt_init($conn);
	if (!mysqli_stmt_prepare($stmt, $sql)) {
	 	header("location: ../Signup.php?error=stmtfailed");
		exit();
	}

	mysqli_stmt_bind_param($stmt, "ss", $username, $email);
	mysqli_stmt_execute($stmt);

	// "Get result" returns the results from a prepared statement
	$resultData = mysqli_stmt_get_result($stmt);

	if ($row = mysqli_fetch_assoc($resultData)) {
		return $row;
	}
	else {
		$result = false;
		return $result;
	}
	mysqli_stmt_close($stmt);
	mysqli_close($conn);
}

// Check if username is in database, if so then return data
function getUInfo($conn, $username) {
	$conn = GetDBConnection();
  $sql = "SELECT * FROM users WHERE usersUid = ? OR usersEmail = ?;";
	$stmt = mysqli_stmt_init($conn);
	if (!mysqli_stmt_prepare($stmt, $sql)) {
		mysqli_close($conn);
	 	header("location: ../Signup.php?error=stmtfailed");
		exit();
	}

	mysqli_stmt_bind_param($stmt, "ss", $username, $email);
	mysqli_stmt_execute($stmt);

	// "Get result" returns the results from a prepared statement
	$resultData = mysqli_stmt_get_result($stmt);

	if ($row = mysqli_fetch_assoc($resultData)) {
		mysqli_close($conn);
		return $row;
	}
	else {
		$result = false;
		mysqli_close($conn);
		return $result;
	}
	mysqli_stmt_close($stmt);
	mysqli_close($conn);
}

// Insert new user into database
function createUser($conn, $username, $email, $pwd) {
	$conn = GetDBConnection();
  $sql = "INSERT INTO users (usersUid, usersEmail, usersPwd) VALUES (?, ?, ?);";

	$stmt = mysqli_stmt_init($conn);
	if (!mysqli_stmt_prepare($stmt, $sql)) {
	 	header("location: ../Signup.php?error=stmtfailed");
		exit();
	}

	$hashedPwd = password_hash($pwd, PASSWORD_DEFAULT);

	mysqli_stmt_bind_param($stmt, "sss", $username, $email, $hashedPwd);
	mysqli_stmt_execute($stmt);
	mysqli_stmt_close($stmt);
	mysqli_close($conn);
	header("location: ../Signup.php?error=none");
	exit();
}

// Check for empty input login
function emptyInputLogin($username, $pwd) {
	if (empty($username) || empty($pwd)) {
		$result = true;
	}
	else {
		$result = false;
	}
	return $result;
}

// Log user into website
function loginUser($username, $pwd, $rememberMe) {
	$conn = GetDBConnection();
	$uidExists = uidExists($conn, $username);

	if ($uidExists === false) {
		mysqli_close($conn);
		header("location: ../Login.php?error=wronglogin");
		exit();
	}

	$pwdHashed = $uidExists["usersPwd"];
	$checkPwd = password_verify($pwd, $pwdHashed);

	if ($checkPwd === false) {
		mysqli_close($conn);
		header("location: ../Login.php?error=wronglogin");
		exit();
	}
	elseif ($checkPwd === true) {
		if(session_status() !== PHP_SESSION_ACTIVE) session_start();
		$_SESSION["userid"] = $uidExists["usersID"];
		$_SESSION["useruid"] = $uidExists["usersUid"];
		$_SESSION["useremail"] = $uidExists["usersEmail"];
		$_SESSION["userspwd"] = $uidExists["usersPwd"];
		$patreonAccessToken = $uidExists["patreonAccessToken"];
		PatreonLogin($patreonAccessToken);

		if($rememberMe)
		{
			$cookie = hash("sha256", rand() . $_SESSION["userspwd"] . rand());
			setcookie("rememberMeToken", $cookie, time() + (86400 * 90), "/");
			storeRememberMeCookie($conn, $_SESSION["useruid"], $cookie);
		}

		mysqli_close($conn);
		header("location: ../MainMenu.php?error=none");
		exit();
	}
}

// Log Google user into website
function loginGoogleUser($id_token) {
	$conn = GetDBConnection();
  $CLIENT_ID = "1089547347578-lqbvoqb0u3grqi89ibjmti2bc9lq37pr.apps.googleusercontent.com";
  $client = new Google_Client(['client_id' => $CLIENT_ID]);  // Specify the CLIENT_ID of the app that accesses the backend
  
  // Verify login is authentic
  $payload = $client->verifyIdToken($id_token);

  // Prepare and bind statement
  $stmt = $conn->prepare("SELECT usersID, usersUid, usersEmail FROM users WHERE usersEmail=?");

  // Declaring string to database. Helps mitigate SQL Injection attacks
  $stmt->bind_param("s", $email);

  // Set parameters and execute
  $email = $payload["email"];
  $stmt->execute();

  // Access the data
  $stmt->store_result();
  $stmt->bind_result($usersID, $usersUid, $usersEmail);
  $stmt->fetch();
  
  // Create the session
  if(session_status() !== PHP_SESSION_ACTIVE) session_start(); {
    $_SESSION["userid"] = $usersID;
    $_SESSION["useruid"] = $usersUid;
    $_SESSION["useremail"] = $usersEmail;  
    
    $stmt->close();
    $conn->close();
    
    header("location: ../MainMenu.php?error=none");
    exit;
  }

  
}

function loginFromCookie()
{
	$conn = GetDBConnection();

  // Prepare and bind statement
  $stmt = $conn->prepare("SELECT usersID, usersUid, usersEmail, patreonAccessToken, patreonRefreshToken FROM users WHERE rememberMeToken=?");
  
  // Set parameters
  $token = $_COOKIE["rememberMeToken"];

  // Declaring string to database. Helps mitigate SQL Injection attacks
  $stmt->bind_param("s", $token);

  // Execute statement
  $stmt->execute();

  // Access the data
  $stmt->store_result();
  $stmt->bind_result($usersID, $usersUid, $usersEmail, $patreonAccessToken, $patreonRefreshToken);
  $stmt->fetch();

  $stmt->close();

  $_SESSION["userid"] = $usersID;
  $_SESSION["useruid"] = $usersUid;
  $_SESSION["useremail"] = $usersEmail;

  PatreonLogin($patreonAccessToken);

  $conn->close();

	// $sql = "SELECT usersID, usersUid, usersEmail, patreonAccessToken, patreonRefreshToken FROM users WHERE rememberMeToken='$token'";
	// $stmt = mysqli_stmt_init($conn);
	// if (mysqli_stmt_prepare($stmt, $sql)) {
	// 	mysqli_stmt_execute($stmt);
	// 	$data = mysqli_stmt_get_result($stmt);
	// 	$row = mysqli_fetch_array($data, MYSQLI_NUM);
	// 	mysqli_stmt_close($stmt);
		// if($row != null && count($row) > 0)
		// {
		// 	$_SESSION["userid"] = $row[0];
		// 	$_SESSION["useruid"] = $row[1];
		// 	$_SESSION["useremail"] = $row[2];
		// 	$patreonAccessToken = $row[3];
		// 	$patreonRefreshToken = $row[4];
		// 	PatreonLogin($patreonAccessToken);
		// }
		// else {
		// 	unset($_SESSION["userid"]);
		// 	unset($_SESSION["useruid"]);
		// 	unset($_SESSION["useremail"]);
		// }
	// }
	mysqli_close($conn);
}

function storeRememberMeCookie($conn, $uuid, $cookie)
{
  // Prepare and bind statement
  $stmt = $conn->prepare("UPDATE users SET rememberMeToken='$cookie' WHERE usersUid=?");

  // Declaring string to database. Helps mitigate SQL Injection attacks
  $stmt->bind_param("s", $uuid);

  // Execute statement
  $stmt->execute();

  $stmt->close();

  // $sql = "UPDATE users SET rememberMeToken='$cookie' WHERE usersUid='$uuid'";
	// $stmt = mysqli_stmt_init($conn);
	// if (mysqli_stmt_prepare($stmt, $sql)) {
	// 	mysqli_stmt_execute($stmt);
	// 	mysqli_stmt_close($stmt);
	// }
}

function addFavoriteDeck($userID, $decklink, $deckName, $heroID)
{
	$conn = GetDBConnection();
	$values = "'" . $decklink . "'," . $userID . ",'" . $deckName . "','" . $heroID . "'";
	$sql = "INSERT IGNORE INTO favoritedeck (decklink, usersId, name, hero) VALUES (" . $values. ");";
	$stmt = mysqli_stmt_init($conn);
	if (mysqli_stmt_prepare($stmt, $sql)) {
		mysqli_stmt_execute($stmt);
		mysqli_stmt_close($stmt);
	}
	mysqli_close($conn);
}

function LoadFavoriteDecks($userID)
{
	$conn = GetDBConnection();
	$sql = "SELECT decklink, name, hero from favoritedeck where usersId=$userID";
	$stmt = mysqli_stmt_init($conn);
	$output = [];
	if (mysqli_stmt_prepare($stmt, $sql)) {
		mysqli_stmt_execute($stmt);
		$data = mysqli_stmt_get_result($stmt);
	  while($row = mysqli_fetch_array($data, MYSQLI_NUM)) {
			for($i=0;$i<3;++$i) array_push($output, $row[$i]);
		}
		mysqli_stmt_close($stmt);
	}
	mysqli_close($conn);
	return $output;
}

function logCompletedGameStats() {
	global $winner, $currentTurn, $gameName;//gameName is assumed by ParseGamefile.php
	global $p1id, $p2id;
	$loser = ($winner == 1 ? 2 : 1);
	$columns = "WinningHero, LosingHero, NumTurns, WinnerDeck, LoserDeck";
	$values = "?, ?, ?, ?, ?";
	$winnerDeck = file_get_contents("./Games/" . $gameName . "/p" . $winner . "Deck.txt");
	$loserDeck = file_get_contents("./Games/" . $gameName . "/p" . $loser . "Deck.txt");
	if($p1id != "" && $p1id != "-")
	{
		$columns .= ", " . ($winner == 1 ? "WinningPID" : "LosingPID");
		$values .= ", " . $p1id;
	}
	if($p2id != "" && $p2id != "-")
	{
		$columns .= ", " . ($winner == 2 ? "WinningPID" : "LosingPID");
		$values .= ", " . $p2id;
	}

	$conn = GetDBConnection();

  $sql = "INSERT INTO completedgame (" . $columns . ") VALUES (" . $values . ");";
	$stmt = mysqli_stmt_init($conn);
	if (mysqli_stmt_prepare($stmt, $sql)) {
		$winHero = &GetPlayerCharacter($winner);
		$loseHero = &GetPlayerCharacter($loser);
		mysqli_stmt_bind_param($stmt, "sssss", $winHero[0], $loseHero[0], $currentTurn, $winnerDeck, $loserDeck);
		mysqli_stmt_execute($stmt);
		mysqli_stmt_close($stmt);
	}
	mysqli_close($conn);

}

function SavePatreonTokens($accessToken, $refreshToken)
{
	if(!isset($_SESSION["userid"])) return;
	$userID = $_SESSION["userid"];
	$conn = GetDBConnection();
	$sql = "UPDATE users SET patreonAccessToken='$accessToken', patreonRefreshToken='$refreshToken' WHERE usersid='$userID'";
	$stmt = mysqli_stmt_init($conn);
	if (mysqli_stmt_prepare($stmt, $sql)) {
		mysqli_stmt_execute($stmt);
		mysqli_stmt_close($stmt);
	}
	mysqli_close($conn);
}
