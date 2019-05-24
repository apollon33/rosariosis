<?php
/**
 * Transcripts functions
 */

if ( ! function_exists( 'TranscriptsIncludeForm' ) )
{
	/**
	 * Get Include on Transcript form
	 *
	 * @todo Use Inputs.php functions.
	 *
	 * @example $return = TranscriptsIncludeForm();
	 *
	 * @since 4.0 Define your custom function in your addon module or plugin.
	 *
	 * @global $extra Get $extra['search'] for Mailing Labels Widget
	 *
	 * @uses _getOtherAttendanceCodes()
	 *
	 * @param  string  $include_on_title Form title (optional). Defaults to 'Include on Transcript'.
	 * @param  boolean $mailing_labels   Include Mailing Labels widget (optional). Defaults to true.
	 * @return string  Include on Transcript form
	 */
	function TranscriptsIncludeForm( $include_on_title = 'Include on Transcript', $mailing_labels = true )
	{
		global $extra;

		$return = '<table class="width-100p">';

		$return .= '<tr><td colspan="2"><b>' . _( 'Include on Transcript' ) .
		'</b><input type="hidden" name="SCHOOL_ID" value="' . UserSchool() . '" /><br /></td></tr>';

		// FJ history grades & previous school years in Transripts.

		if ( User( 'PROFILE' ) === 'admin' )
		{
			$syear_history_RET = DBGet( "SELECT DISTINCT SYEAR
				FROM HISTORY_MARKING_PERIODS
				WHERE SYEAR<>'" . UserSyear() . "'
				AND SCHOOL_ID='" . UserSchool() . "'
				UNION SELECT DISTINCT SYEAR
				FROM SCHOOL_MARKING_PERIODS
				WHERE SYEAR<>'" . UserSyear() . "'
				AND SCHOOL_ID='" . UserSchool() . "'
				ORDER BY SYEAR DESC" );

			// If History School Years or previous school years.

			if ( $syear_history_RET )
			{
				$return .= '<tr class="st"><td>';

				$syoptions[UserSyear()] = FormatSyear( UserSyear(), Config( 'SCHOOL_SYEAR_OVER_2_YEARS' ) );

				// Chosen Multiple select input.
				$syextra = 'multiple';

				foreach ( (array) $syear_history_RET as $syear_history )
				{
					$syoptions[$syear_history['SYEAR']] = FormatSyear(
						$syear_history['SYEAR'],
						Config( 'SCHOOL_SYEAR_OVER_2_YEARS' )
					);
				}

				$return .= ChosenSelectInput(
					UserSyear(),
					'syear_arr[]',
					_( 'School Years' ),
					$syoptions,
					false,
					$syextra,
					false
				);

				$return .= '<hr /></td></tr>';
			}
		}

		$mp_types = DBGet( "SELECT DISTINCT MP_TYPE
			FROM MARKING_PERIODS
			WHERE NOT MP_TYPE IS NULL
			AND SCHOOL_ID='" . UserSchool() . "'", array(), array() );

		$return .= '<tr class="st"><td class="valign-top">';

		//FJ add translation
		$marking_periods_locale = array(
			'Year' => _( 'Year' ),
			'Semester' => _( 'Semester' ),
			'Quarter' => _( 'Quarter' ),
		);

		foreach ( (array) $mp_types as $mp_type )
		{
			//FJ add <label> on checkbox
			$return .= '<label><input type="checkbox" name="mp_type_arr[]" value="' . $mp_type['MP_TYPE'] . '"> ' . $marking_periods_locale[ucwords( $mp_type['MP_TYPE'] )] . '</label> ';
		}

		$return .= FormatInputTitle( _( 'Marking Periods' ) ) . '<hr /></td></tr>';

		$return .= '<tr class="st"><td class="valign-top">';

		//FJ add Show Grades option
		$return .= '<label><input type="checkbox" name="showgrades" value="1" checked /> ' . _( 'Grades' ) . '</label>';

		$return .= '<br /><br /><label><input type="checkbox" name="showstudentpic" value="1"> ' . _( 'Student Photo' ) . '</label>';

		//FJ add Show Comments option
		$return .= '<br /><br /><label><input type="checkbox" name="showmpcomments" value="1"> ' . _( 'Comments' ) . '</label>';

		//FJ add Show Credits option
		$return .= '<br /><br /><label><input type="checkbox" name="showcredits" value="1" checked /> ' . _( 'Credits' ) . '</label>';

		//FJ add Show Credit Hours option
		$return .= '<br /><br /><label><input type="checkbox" name="showcredithours" value="1"> ' . _( 'Credit Hours' ) . '</label>';

		//FJ limit Cetificate to admin

		if ( User( 'PROFILE' ) === 'admin' )
		{
			//FJ add Show Studies Certificate option
			$field_SSECURITY = ParseMLArray( DBGet( "SELECT TITLE
				FROM CUSTOM_FIELDS
				WHERE ID = 200000003" ), 'TITLE' );

			$return .= '<br /><br /><label><input type="checkbox" name="showcertificate" autocomplete="off" value="1" onclick=\'javascript: document.getElementById("divcertificatetext").style.display="block"; document.getElementById("inputcertificatetext").focus();\'> ' . _( 'Studies Certificate' ) . '</label>';
		}

		//FJ limit Cetificate to admin

		if ( User( 'PROFILE' ) === 'admin' )
		{
			//FJ add Show Studies Certificate option
			$return .= '<div id="divcertificatetext" style="display:none">';

			$return .= TinyMCEInput(
				GetTemplate(),
				'inputcertificatetext',
				_( 'Studies Certificate Text' )
			);

			$substitutions = array(
				'__SSECURITY__' => $field_SSECURITY[1]['TITLE'],
				'__FULL_NAME__' => _( 'Display Name' ),
				'__LAST_NAME__' => _( 'Last Name' ),
				'__FIRST_NAME__' => _( 'First Name' ),
				'__MIDDLE_NAME__' =>  _( 'Middle Name' ),
				'__GRADE_ID__' => _( 'Grade Level' ),
				'__NEXT_GRADE_ID__' => _( 'Next Grade' ),
				'__SCHOOL_ID__' => _( 'School' ),
				'__YEAR__' => _( 'School Year' ),
				'__BLOCK2__' => _( 'Text Block 2' ),
			);

			$return .= '<table><tr class="st"><td class="valign-top">' .
				SubstitutionsInput( $substitutions ) .
			'</td></tr>';

			$return .= '</table></div>';
		}

		$return .= '</td></tr></table>';

		return $return;
	}
}

if ( ! function_exists( 'TranscriptsGenerate' ) )
{
	/**
	 * Transcripts generation
	 *
	 * @todo Divide in smaller functions
	 *
	 * @example $transcripts = TranscriptsGenerate( $_REQUEST['st_arr'], $_REQUEST['mp_arr'] );
	 *
	 * @since 4.8 Define your custom function in your addon module or plugin.
	 * @since 4.8 Add Transcripts PDF header action hook.
	 *
	 * @uses _makeTeacher() see below
	 *
	 * @param  array         $student_array Students IDs.
	 * @param  array         $mp_type_array Marking Periods types.
	 * @param  array         $syear_array   School Years.
	 * @return boolean|array False if No Students or Transcripts associative array (key = $student_id)
	 */
	function TranscriptsGenerate( $student_array, $mp_type_array, $syear_array )
	{
		global $_ROSARIO,
			$count_lines;

		if ( empty( $student_array )
			|| empty( $mp_type_array ) )
		{
			return false;
		}

		// Limit School & Year to current ones if not admin.
		$syear_list = ( User( 'PROFILE' ) === 'admin' && $syear_array ?
			"'" . implode( "','", $syear_array ) . "'" :
			"'" . UserSyear() . "'" );

		$school_id = ( User( 'PROFILE' ) === 'admin' && $_REQUEST['SCHOOL_ID'] ? $_REQUEST['SCHOOL_ID'] : UserSchool() );

		$mp_type_list = "'" . implode( "','", $mp_type_array ) . "'";

		$st_list = "'" . implode( "','", $student_array ) . "'";

		$RET = 1;

		if ( User( 'PROFILE' ) !== 'admin' )
		{
			// FJ prevent student ID hacking.
			$extra['WHERE'] = " AND s.STUDENT_ID IN (" . $st_list . ")";

			// Parent: associated students.
			$extra['ASSOCIATED'] = User( 'STAFF_ID' );

			$RET = GetStuList( $extra );
		}

		$t_grades = DBGet( "SELECT *
			FROM transcript_grades
			WHERE student_id IN (" . $st_list . ")
			AND mp_type in (" . $mp_type_list . ")
			AND school_id='" . $school_id . "'
			AND syear in (" . $syear_list . ")
			ORDER BY mp_type, end_date", array(), array( 'STUDENT_ID', 'SYEAR', 'MARKING_PERIOD_ID' ) );

		if ( empty( $t_grades ) || empty( $RET ) )
		{
			return array();
		}

		$syear = ( User( 'PROFILE' ) === 'admin' && $_REQUEST['syear_arr'] ?
			$_REQUEST['syear_arr'][0] :
			UserSyear() );

		$show = array();

		$show['studentpic'] = isset( $_REQUEST['showstudentpic'] ) ? $_REQUEST['showstudentpic'] : '';

		$show['sat'] = isset( $_REQUEST['showsat'] ) ? $_REQUEST['showsat'] : '';

		$show['grades'] = isset( $_REQUEST['showgrades'] ) ? $_REQUEST['showgrades'] : '';

		$show['mpcomments'] = isset( $_REQUEST['showmpcomments'] ) ? $_REQUEST['showmpcomments'] : '';

		$show['credits'] = isset( $_REQUEST['showcredits'] ) ? $_REQUEST['showcredits'] : '';

		$show['credithours'] = isset( $_REQUEST['showcredithours'] ) ? $_REQUEST['showcredithours'] : '';

		$show['certificate'] = User( 'PROFILE' ) === 'admin' && ! empty( $_REQUEST['showcertificate'] );

		if ( $show['certificate'] )
		{
			// FJ bypass strip_tags on the $_REQUEST vars.
			$REQUEST_inputcertificatetext = SanitizeHTML( $_POST['inputcertificatetext'] );

			SaveTemplate( $REQUEST_inputcertificatetext );

			$block2 = '__BLOCK2__';

			if ( mb_strpos( $REQUEST_inputcertificatetext, '<p>__BLOCK2__</p>' ) !== false )
			{
				$block2 = '<p>__BLOCK2__</p>';
			}

			$certificate_texts = explode( $block2, $REQUEST_inputcertificatetext );
		}

		$custom_fields_RET = DBGet( "SELECT ID,TITLE,TYPE
			FROM CUSTOM_FIELDS WHERE ID IN (200000000, 200000003, 200000004)", array(), array( 'ID' ) );

		if ( $custom_fields_RET['200000000']
			&& $custom_fields_RET['200000000'][1]['TYPE'] == 'select' )
		{
			$students_dataquery .= ", s.custom_200000000 as gender";
		}

		if ( $custom_fields_RET['200000003'] )
		{
			$students_dataquery .= ", s.custom_200000003 as ssecurity";
		}

		if ( $custom_fields_RET['200000004']
			&& $custom_fields_RET['200000004'][1]['TYPE'] == 'date' )
		{
			$students_dataquery .= ", s.custom_200000004 as birthdate";
		}

		$students_data = _getTranscriptsStudents( $st_list, $syear );

		$school_info = DBGet( "SELECT * FROM SCHOOLS
			WHERE SYEAR='" . UserSyear() . "'
			AND ID='" . $school_id . "'" );

		$school_info = $school_info[1];

		// Transcripts array.
		$transcripts = array();

		foreach ( (array) $t_grades as $student_id => $t_sgrades )
		{
			$student = $students_data[$student_id][1];

			foreach ( (array) $t_sgrades as $syear => $mps )
			{
				if ( $show['certificate'] )
				{
					$substitutions = array(
						'__SSECURITY__' => $student['SSECURITY'],
						'__FULL_NAME__' => $student['FULL_NAME'],
						'__LAST_NAME__' => $student['LAST_NAME'],
						'__FIRST_NAME__' => $student['FIRST_NAME'],
						'__MIDDLE_NAME__' => $student['MIDDLE_NAME'],
						'__GRADE_ID__' => $student['GRADE_LEVEL'],
						'__NEXT_GRADE_ID__' => $student['NEXT_GRADE_LEVEL'],
						'__SCHOOL_ID__' => $school_info['TITLE'],
						'__YEAR__' => $syear,
					);

					$certificate_block1 = SubstitutionsTextMake( $substitutions, $certificate_texts[0] );

					if ( ! empty( $certificate_texts[1] ) )
					{
						$certificate_block2 = SubstitutionsTextMake( $substitutions, $certificate_texts[1] );
					}
				}

				$transcript = '<table class="width-100p"><tr class="valign-top"><td>';


				if ( $show['studentpic'] )
				{
					//Student Photo.
					$stu_pic = $StudentPicturesPath . Config( 'SYEAR' ) . '/' . $student_id . '.jpg';
					$stu_pic2 = $StudentPicturesPath . $syear . '/' . $student_id . '.jpg';
					$picwidth = 70;

					if ( file_exists( $stu_pic ) )
					{
						$transcript .= '<img src="' . $stu_pic . '" width="' . $picwidth . '" />';
					}
					elseif ( file_exists( $stu_pic2 ) )
					{
						$transcript .= '<img src="' . $stu_pic2 . '" width="' . $picwidth . '" />';
					}
					else
					{
						$transcript .= '&nbsp;';
					}
				}

				// Student Info.
				$transcript .= '</td><td><span style="font-size:x-large;">' . $student['FULL_NAME'] . '<br /></span>';

				// Translate "No Address".
				$transcript .= '<span>' . ( $student['ADDRESS'] === 'No Address' ?
					_( 'No Address' ) : $student['ADDRESS'] ) . '<br /></span>';

				$transcript .= '<span>' . $student['CITY'] .
					( ! empty( $student['STATE'] ) ? ', ' . $student['STATE'] : '' ) .
					( ! empty( $student['ZIPCODE'] ) ? '  ' . $student['ZIPCODE'] : '' ) . '</span>';

				$transcript .= '<table class="cellspacing-0 cellpadding-5" style="margin-top:10px;"><tr>';

				if ( $custom_fields_RET['200000004']
					&& $custom_fields_RET['200000004'][1]['TYPE'] == 'date' )
				{
					$transcript .= '<td style="border:solid black; border-width:1px 0 1px 1px;">' .
						ParseMLField( $custom_fields_RET['200000004'][1]['TITLE'] ) . '</td>';
				}

				if ( $custom_fields_RET['200000000']
					&& $custom_fields_RET['200000000'][1]['TYPE'] == 'select' )
				{
					$transcript .= '<td style="border:solid black; border-width:1px 0 1px 1px;">' .
						ParseMLField( $custom_fields_RET['200000000'][1]['TITLE'] ) . '</td>';
				}

				$transcript .= '<td style="border:solid black; border-width:1px;">' .
					_( 'Grade Level' ) . '</td></tr><tr>';

				if ( $custom_fields_RET['200000004']
					&& $custom_fields_RET['200000004'][1]['TYPE'] == 'date' )
				{
					$dob = explode( '-', $student['BIRTHDATE'] );

					if ( ! empty( $dob ) )
					{
						$transcript .= '<td class="center">' . $dob[1] . '/' . $dob[2] . '/' . $dob[0] . '</td>';
					}
					else
					{
						$transcript .= '<td>&nbsp;</td>';
					}
				}

				if ( $custom_fields_RET['200000000']
					&& $custom_fields_RET['200000000'][1]['TYPE'] == 'select' )
				{
					$transcript .= '<td class="center">' . $student['GENDER'] . '</td>';
				}

				if ( empty( $student['GRADE_LEVEL'] ) )
				{
					// FJ history grades in Transripts.
					$student['GRADE_LEVEL'] = $mps[key( $mps )][1]['GRADE_LEVEL_SHORT'];
				}

				$transcript .= '<td class="center">' . $student['GRADE_LEVEL'] . '</td></tr></table></td>';

				// School logo.
				$logo_pic = 'assets/school_logo_' . UserSchool() . '.jpg';
				$picwidth = 120;

				$transcript .= '<td style="width:' . $picwidth . 'px;">';

				if ( file_exists( $logo_pic ) )
				{
					$transcript .= '<img src="' . $logo_pic . '" width="' . $picwidth . '" />';
				}

				// School Info.
				$transcript .= '</td><td style="width:384px;">';

				$transcript .= '<span style="font-size:x-large;">' . $school_info['TITLE'] . '<br /></span>';

				$transcript .= '<span>' . $school_info['ADDRESS'] . '<br /></span>';

				$transcript .= '<span>' . $school_info['CITY'] .
					( ! empty( $school_info['STATE'] ) ? ', ' . $school_info['STATE'] : '' ) .
					( ! empty( $school_info['ZIPCODE'] ) ? '  ' . $school_info['ZIPCODE'] : '' ) . '<br /></span>';

				if ( $school_info['PHONE'] )
				{
					$transcript .= '<span>' . _( 'Phone' ) . ': ' . $school_info['PHONE'] . '<br /></span>';
				}

				if ( $school_info['WWW_ADDRESS'] )
				{
					$transcript .= '<span>' . _( 'Website' ) . ': ' . $school_info['WWW_ADDRESS'] . '<br /></span>';
				}

				if ( $school_info['SCHOOL_NUMBER'] )
				{
					$transcript .= '<span>' . _( 'School Number' ) . ': ' . $school_info['SCHOOL_NUMBER'] .
						'<br /><br /></span>';
				}

				$transcript .= '<span>' . $school_info['PRINCIPAL'] . '<br /></span></td></tr>';

				if ( $show['certificate'] )
				{
					// Certificate Text block 1.
					$transcript .= '<tr><td colspan="4"><br />' . $certificate_block1 . '</td></tr>';
				}

				$transcript .= '</table>';

				// Generate ListOutput friendly array.
				$grades_RET = array();

				$total_credit_earned = 0;
				$total_credit_attempted = 0;

				$columns = array( 'COURSE_TITLE' => _( 'Course' ) );

				foreach ( (array) $mps as $mp_id => $grades )
				{
					$columns[$mp_id] = $grades[1]['SHORT_NAME'];

					foreach ( (array) $grades as $grade )
					{
						$i = $grade['COURSE_TITLE'];

						$grades_RET[$i]['COURSE_TITLE'] = $grade['COURSE_TITLE'];

						if ( $show['grades'] )
						{
							if ( ProgramConfig( 'grades', 'GRADES_DOES_LETTER_PERCENT' ) > 0 )
							{
								$grades_RET[$i][$mp_id] = $grade['GRADE_PERCENT'] . '%';
							}
							elseif ( ProgramConfig( 'grades', 'GRADES_DOES_LETTER_PERCENT' ) < 0 )
							{
								$grades_RET[$i][$mp_id] = $grade['GRADE_LETTER'];
							}
							else
							{
								$grades_RET[$i][$mp_id] = $grade['GRADE_LETTER'] . '&nbsp;&nbsp;' . $grade['GRADE_PERCENT'] . '%';
							}
						}

						if ( $show['credits'] )
						{
							if ( ( strpos( $mp_type_list, 'year' ) !== false
									&& $grade['MP_TYPE'] != 'quarter'
									&& $grade['MP_TYPE'] != 'semester' )
								|| ( strpos( $mp_type_list, 'semester' ) !== false
									&& $grade['MP_TYPE'] != 'quarter' )
								|| ( strpos( $mp_type_list, 'year' ) === false
									&& strpos( $mp_type_list, 'semester' ) === false
									&& $grade['MP_TYPE'] == 'quarter' ) )
							{
								$grades_RET[$i]['CREDIT_EARNED'] += (float) $grade['CREDIT_EARNED'];

								$total_credit_earned += $grade['CREDIT_EARNED'];
								$total_credit_attempted += $grade['CREDIT_ATTEMPTED'];
							}
						}

						if ( $show['credithours']
							&& ! isset( $grades_RET[$i]['CREDIT_HOURS'] ) )
						{
							$grades_RET[$i]['CREDIT_HOURS'] = (int) $grade['CREDIT_HOURS'] == $grade['CREDIT_HOURS'] ?
								(int) $grade['CREDIT_HOURS'] :
								$grade['CREDIT_HOURS'];
						}

						if ( $show['mpcomments'] )
						{
							$grades_RET[$i]['COMMENT'] = $grade['COMMENT'];
						}
					}
				}

				if ( $show['credits'] )
				{
					$columns['CREDIT_EARNED'] = _( 'Credit' );
				}

				if ( $show['credithours'] )
				{
					$columns['CREDIT_HOURS'] = _( 'C.H.' );
				}

				if ( $show['mpcomments'] )
				{
					$columns['COMMENT'] = _( 'Comment' );
				}

				$grades_RET = array_values( $grades_RET );
				array_unshift( $grades_RET, 'start_array_to_1' );
				unset( $grades_RET[0] );
				//var_dump($grades_RET);exit;

				$transcript .= '<br />';

				ob_start();

				ListOutput( $grades_RET, $columns, '.', '.', false );

				$transcript .= ob_get_clean();

				// School Year.
				$transcript .= '<table class="width-100p"><tr><td><span><br />' .
					_( 'School Year' ) . ': ' .
					FormatSyear( $syear, Config( 'SCHOOL_SYEAR_OVER_2_YEARS' ) ) . '</span></td></tr>';

				// GPA and/or Class Rank.

				if ( $show['grades']
					&& $grade['MP_TYPE'] !== 'quarter'
					&& ( ! empty( $grade['CUM_WEIGHTED_GPA'] ) || ! empty( $grade['CUM_RANK'] ) ) )
				{
					$transcript .= '<tr><td><span>';

					if ( ! empty( $grade['CUM_WEIGHTED_GPA'] ) )
					{
						$transcript .= sprintf(
							_( 'GPA' ) . ': %01.2f / %01.0f',
							$grade['CUM_WEIGHTED_GPA'],
							$grade['SCHOOL_SCALE'] );

						if ( ! empty( $grade['CUM_RANK'] ) )
						{
							$transcript .= ' &ndash; ';
						}
					}

					if ( ! empty( $grade['CUM_RANK'] ) )
					{
						$transcript .= _( 'Class Rank' ) . ': ' . $grade['CUM_RANK'] .
							' / ' . $grade['CLASS_SIZE'] . '</span>';
					}

					$transcript .= '</span></td></tr>';
				}

				if ( $show['credits']
					&& $total_credit_attempted > 0 )
				{
					// Total Credits.
					$transcript .= '<tr><td><span>';
					$transcript .= _( 'Total' ) . ' ' . _( 'Credit' ) . ': ' .
					_( 'Credit Attempted' ) . ': ' . (float) $total_credit_attempted .
					' &ndash; ' . _( 'Credit Earned' ) . ': ' . (float) $total_credit_earned;
					$transcript .= '</span></td></tr>';
				}

				if ( $show['certificate'] && ! empty( $certificate_block2 ) )
				{
					// Certificate Text block 2.
					$transcript .= '<tr><td><br />' . $certificate_block2 . '</td></tr>';
				}

				$transcript .= '</table>';

				$transcript .= '</td></tr></table>';

				$transcripts[] = $transcript;
			}
		}

		return $transcripts;
	}
}


function _getTranscriptsStudents( $st_list, $syear )
{
	$students_dataquery = "SELECT
	s.STUDENT_ID,
	s.FIRST_NAME,
	s.LAST_NAME,
	s.MIDDLE_NAME,
	" . DisplayNameSQL( 's' ) . " AS FULL_NAME";

	$custom_fields_RET = DBGet( "SELECT ID,TITLE,TYPE
		FROM CUSTOM_FIELDS WHERE ID IN (200000000, 200000003, 200000004)", array(), array( 'ID' ) );

	if ( $custom_fields_RET['200000000']
		&& $custom_fields_RET['200000000'][1]['TYPE'] == 'select' )
	{
		$students_dataquery .= ", s.custom_200000000 as gender";
	}

	if ( $custom_fields_RET['200000003'] )
	{
		$students_dataquery .= ", s.custom_200000003 as ssecurity";
	}

	if ( $custom_fields_RET['200000004']
		&& $custom_fields_RET['200000004'][1]['TYPE'] == 'date' )
	{
		$students_dataquery .= ", s.custom_200000004 as birthdate";
	}

	//, s.custom_200000012 as estgraddate
	$students_dataquery .= ", a.address
	, a.city
	, a.state
	, a.zipcode
	, a.phone
	, a.mail_address
	, a.mail_city
	, a.mail_state
	, a.mail_zipcode
	, (SELECT start_date FROM student_enrollment
		WHERE student_id=s.student_id
		ORDER BY syear, start_date
		LIMIT 1) as init_enroll
	, (SELECT sgl.title
		FROM school_gradelevels sgl JOIN student_enrollment se ON (sgl.id=se.grade_id)
		WHERE se.syear='" . $syear . "'
		AND se.student_id=s.student_id
		AND (se.end_date is null OR se.start_date < se.end_date)
		ORDER BY se.start_date desc
		LIMIT 1) as grade_level
	, (SELECT sgl2.title
		FROM school_gradelevels sgl2, school_gradelevels sgl JOIN student_enrollment se ON (sgl.id=se.grade_id)
		WHERE se.syear='" . $syear . "'
		AND se.student_id=s.student_id
		AND (se.end_date is null OR se.start_date < se.end_date)
		AND sgl2.id=sgl.next_grade_id
		ORDER BY se.start_date desc
		LIMIT 1) as next_grade_level
	FROM students s
	LEFT OUTER JOIN students_join_address sja ON (sja.student_id=s.student_id)
	LEFT OUTER JOIN address a ON (a.address_id=sja.address_id) ";

	$students_RET = DBGet( $students_dataquery .
		' WHERE s.student_id IN (' . $st_list . ')
		ORDER BY LAST_NAME,FIRST_NAME', array(), array( 'STUDENT_ID' ) );

	return $students_RET;
}