<?php if (!defined('TL_ROOT')) die('You can not access this file directly!');

/**
 * Contao Open Source CMS
 * Copyright (C) 2005-2010 Leo Feyer
 *
 * Formerly known as TYPOlight Open Source CMS.
 *
 * This program is free software: you can redistribute it and/or
 * modify it under the terms of the GNU Lesser General Public
 * License as published by the Free Software Foundation, either
 * version 3 of the License, or (at your option) any later version.
 * 
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU
 * Lesser General Public License for more details.
 * 
 * You should have received a copy of the GNU Lesser General Public
 * License along with this program. If not, please visit the Free
 * Software Foundation website at <http://www.gnu.org/licenses/>.
 *
 * PHP version 5
 * @copyright  Tim Gatzky 2012 
 * @author     Tim Gatzky <info@tim-gatzky.de>
 * @package    calendar_autorepeat 
 * @license    LGPL 
 * @filesource
 */


/**
 * Subpalettes
 */
$GLOBALS['TL_DCA']['tl_calendar_events']['subpalettes']['recurring'] = 'repeatEndOverride,repeatEnd,'.$GLOBALS['TL_DCA']['tl_calendar_events']['subpalettes']['recurring'];


/**
 * Fields
 */
$GLOBALS['TL_DCA']['tl_calendar_events']['fields']['repeatEndOverride'] = array
(
	'label'                   => &$GLOBALS['TL_LANG']['tl_calendar_events']['repeatEnd'],
	'exclude'                 => true,
	'inputType'               => 'text',
	'eval'                    => array('rgxp'=>'date', 'datepicker'=>true, 'tl_class'=>'clr wizard'),
);

# debug only
#$GLOBALS['TL_DCA']['tl_calendar_events']['fields']['repeatEnd'] = array
#(
#	'label'                   => &$GLOBALS['TL_LANG']['tl_calendar_events']['repeatEnd'],
#	'exclude'                 => true,
#	'inputType'               => 'text',
#	'eval'                    => array('rgxp'=>'date', 'readonly'=>true, 'tl_class'=>'clr wizard'),
#);

// overwrite contao function
$GLOBALS['TL_DCA']['tl_calendar_events']['config']['onsubmit_callback'][] = array('tl_calendar_events_calendar_autorepeat','adjustTime');


class tl_calendar_events_calendar_autorepeat extends Backend
{
	/**
	 * Auto Calculate recurrences
	 * Override contao adjustTime()
	 * @param object
	 */
	public function adjustTime(DataContainer $dc)
	{
		if($this->Input->get('act') != 'edit')
		{
			return $dc;
		}
		
		// return if it's not a recurring event or it is endless or if the end recurrence date is smaler than the start date
		if(strlen($dc->activeRecord->repeatEndOverride) < 1 || $dc->activeRecord->recurring < 1 || $dc->activeRecord->repeatEndOverride < $dc->activeRecord->startDate)
		{
			return $dc;
		}
			
		$intStart = $dc->activeRecord->startDate;
		$intEnd = $dc->activeRecord->repeatEndOverride;
		$arrRange = deserialize($dc->activeRecord->repeatEach);
		$unit = $arrRange['unit'];
		$arg = $arrRange['value'];

		$objInterval = date_diff(date_create(date('Y-m-d',$intStart)),date_create(date('Y-m-d',$intEnd)));
		$intDays = $objInterval->days;
		$intMonths = $objInterval->m;
		$intYears = $objInterval->y;
		$intRepeats = 0;
		
		if($unit == 'days')
		{
			$intRepeats = ceil( ($intDays) / $arg);
		}
		else if($unit == 'weeks')
		{
			$intRepeats = ceil( ($intDays / 7) / $arg);
		}
		else if($unit == 'months')
		{
			$intRepeats = ceil( ($intYears*12 + $intMonths) / $arg);
		}
		else if($unit == 'years')
		{
			$intRepeats = ceil( ($intYears) / $arg);
		}
		else{}
		
		
		// update record
		$arrSet['recurrences'] = $intRepeats;
		$arrSet['repeatEnd'] = $dc->activeRecord->repeatEndOverride;
		$this->Database->prepare("UPDATE tl_calendar_events %s WHERE id=?")->set($arrSet)->execute($dc->id);
		
	}
}

?>