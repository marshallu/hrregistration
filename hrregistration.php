<?php
/*
Plugin Name: MU HR Training Registration
Plugin URI: http://www.marshall.edu
Description: Register for Training provided by MU HR
Author: John Cummings
Version: 1.0
*/
/*  Copyright 2011  John Cummings  (email : john@jcummings.net)

	This program is free software; you can redistribute it and/or modify
	it under the terms of the GNU General Public License, version 2, as
	published by the Free Software Foundation.

	This program is distributed in the hope that it will be useful,
	but WITHOUT ANY WARRANTY; without even the implied warranty of
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
	GNU General Public License for more details.

	You should have received a copy of the GNU General Public License
	along with this program; if not, write to the Free Software
	Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/



function hrregistration( $atts ) {
	$data = shortcode_atts(
		array(
			'cname' => 'foo',
		),
		$atts
	);

	$config          = include plugin_dir_path( __FILE__ ) . 'config.php';
	$server_name     = $config['server'];
	$connection_info = array(
		'Database' => $config['database'],
		'UID'      => $config['user'],
		'PWD'      => $config['password'],
	);

	if ( isset( $_GET['action'] ) && isset( $_POST['CourseNo'] ) ) {

		$mycourse = filter_var( $_POST['CourseNo'] );
		$mydate   = date( 'Y-m-d' );

		if ( empty( $_POST['MUID'] ) ) {
			$muid = 000;
		} else {
			$muid = wp_unslash( $_POST['MUID'] );
		}

		//Make sure we got all the info we needed
		if ( empty( $_POST['FirstName'] ) || empty( $_POST['LastName'] ) || empty( $_POST['Department'] ) || empty( $_POST['Phone'] ) || empty( $_POST['email'] ) ) {
			echo "All of the fields on the previous page are required. Your registration has been halted because you did not complete all of the required fields. Please click the back button in your browser, and try again - making sure to complete all fields.<br/>";
			die();
		}

		$check_sql  = "SELECT * FROM Registrations WHERE Email = '" . $_POST['email'] . "' AND CourseNo = '" . $mycourse . "'";
		$check_stmt = sqlsrv_query( $conn, $check_sql, array(), array( 'Scrollable' => 'static' ) );

		if ( sqlsrv_num_rows( $check_stmt ) > 0 ) {
			echo "You've already successfully registered for this course.";
		} else {
			$sql = "INSERT INTO Registrations (MUID,FirstName,LastName,Department,OfficePhone,Email,RegDate,CourseNo) VALUES (?,?,?,?,?,?,?,?)";
			$params = array($muid, $_POST["FirstName"], $_POST["LastName"], $_POST["Department"], $_POST["Phone"], $_POST["email"], $mydate, $mycourse);

			$stmt = sqlsrv_query( $conn, $sql, $params);

			$course_sql = "SELECT * FROM Courses WHERE CourseNo = '" . $mycourse . "';";

			$course_stmt = sqlsrv_query( $conn, $course_sql );

			while( $row = sqlsrv_fetch_array( $course_stmt, SQLSRV_FETCH_ASSOC) ) {
				$course_name = $row['CourseName'];
				$course_location = $row['Location'];
				$course_day = date_format($row['Date'], 'F j');
				$course_start_time = date_format($row['StartTime'], 'g:ia');
				$course_end_time = date_format($row['EndTime'], 'g:ia');
				$course_full_start = date_format($row['Date'], 'Y-m-d') . 'T' . date_format($row['StartTime'], 'H:i:s');
				$course_full_start = date_format($row['Date'], 'Y-m-d') . 'T' . date_format($row['EndTime'], 'H:i:s');
			}

			if( $stmt === false ) {
				echo "Sorry there was an issue with your registration, please try again.";
			   } else {
			   echo "You have successfully registered!";
			   $email = "You have successfully registered for " . $course_name . " at " . $course_location . " on " . $course_day . " at " . $course_start_time . " - " . $course_end_time . ".\r\rFor any questions please contact Human Resources.";
			   $headers = "From: human-resources@marshall.edu";
			   mail($_POST["email"] , "HR Training Registration", $email, $headers);
		   }
		}
	}
	$class_sql  = "SELECT * FROM Courses WHERE CourseNo = '" . esc_attr( $_GET['cnumber'] ) . "'";
	$class_stmt = sqlsrv_query( $conn, $class_sql, array(), array( 'Scrollable' => 'static' ) );

	if ( ! $class_stmt ) {
		die( print_r( sqlsrv_errors(), true) );
	}

	while ( $row = sqlsrv_fetch_array( $class_stmt, SQLSRV_FETCH_ASSOC ) ) {
		$count_sql = "SELECT * FROM Registrations WHERE CourseNo = '" . esc_attr( $_GET['cnumber'] ) . "'";
		$count     = sqlsrv_query( $conn, $count_sql, array(), array( 'Scrollable' => 'static' ) );

		$seats_left = $row['Seats'] - sqlsrv_num_rows( $count );

		if ( $seats_left < 0 ) {
			$seats_left = 0;
		}

		if ( $seats_left > 0 ) {
			if ( isset( $_GET['cnumber'] ) ) {
				$cnumber = filter_var( $_GET['cnumber'], FILTER_SANITIZE_STRING );

				echo '<form method="POST" action="/human-resources/training/course-registration/?action=y" name="hrtraining">';
				echo '<div class="my-2">';
				echo '<label class="block vfb-desc" for="MUID">MUID Number</label>';
				echo '<input type="text" class="text-input" name="MUID" max="9" min="9" placeholder="901xxxxxx" />';
				echo '</div>';
				echo '<div class="my-2">';
				echo '<label class="block vfb-desc" for="FirstName">First Name</label>';
				echo '<input type="text" class="text-input" name="FirstName" required />';
				echo '</div>';
				echo '<div class="my-2">';
				echo '<label class="block vfb-desc" for="LastName">Last Name</label>';
				echo '<input type="text" class="text-input" name="LastName" required />';
				echo '</div>';
				echo '<div class="my-2">';
				echo '<label class="block vfb-desc" for="Department">Department</label>';
				echo '<input type="text" class="text-input" name="Department" required />';
				echo '</div>';
				echo '<div class="my-2">';
				echo '<label class="block vfb-desc" for="Phone">Phone</label>';
				echo '<input type="text" class="text-input" name="Phone" required />';
				echo '</div>';
				echo '<div class="my-2">';
				echo '<label class="block vfb-desc" for="email">Email Address</label>';
				echo '<input type="email" class="text-input" name="email" required />';
				echo '<input type="hidden" name="CourseNo"  value="' . esc_attr( $cnumber ) . '" class="text-input">';
				echo '</div>';
				echo '<div class="mt-4">';
				echo '<input type="submit" name="Submit" value="Submit" class="btn btn-green">';
				echo '</div>';
				echo '</form>';
			} else {
				echo 'Sorry, but this page may not be viewed directly.';
			}
		} else {
			echo 'Sorry registration for this training is full.';
		}
	}
}
add_shortcode( 'registration', 'hrregistration' );
add_shortcode( 'mu_registration', 'hrregistration' );
