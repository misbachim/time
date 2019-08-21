<?php

/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
|
| Here is where you can register all of the routes for an application.
| It is a breeze. Simply tell Lumen the URIs it should respond to
| and give it the Closure to call when that URI is requested.
|
 */

$router->get('/live', 'TestController@live');

$router->group(['prefix' => 'calendar'], function () use ($router) {
    $router->post('getAll', 'CalendarController@getAll');
    $router->post('getAllEvent', 'CalendarController@getAllEventWithEligibilty');
    $router->post('getOne', 'CalendarController@getOne');
    $router->post('save', 'CalendarController@save');
    $router->post('update', 'CalendarController@update');
    $router->post('delete', 'CalendarController@delete');
});

$router->group(['prefix' => 'leave'], function () use ($router) {
    $router->post('getAll', 'LeaveController@getAll');
    $router->post('getAllWithoutFirst', 'LeaveController@getAllWithoutFirst');
    $router->post('getOne', 'LeaveController@getOne');
    $router->post('getFirst', 'LeaveController@getFirst');
    $router->post('getLeaveByEligibilities', 'LeaveController@getLeaveByEligibilities');
    $router->post('getLeaveByEligibilitiesWithQuotas', 'LeaveController@getLeaveByEligibilitiesWithQuotas');
    $router->post('getEligibility', 'LeaveController@getEligibility');
    $router->post('save', 'LeaveController@save');
    $router->post('update', 'LeaveController@update');
    $router->post('delete', 'LeaveController@delete');
    $router->post('lov', 'LeaveController@getLov');
    $router->post('lovEmployee', 'LeaveController@getLovEmployee');
});

$router->group(['prefix' => 'timeGroup'], function () use ($router) {
    $router->post('getAll', 'TimeGroupController@getAll');
    $router->post('getOne', 'TimeGroupController@getOne');
    $router->post('getOneDefault', 'TimeGroupController@getOneDefault');
    $router->post('lov', 'TimeGroupController@getLov');
    $router->post('save', 'TimeGroupController@save');
    $router->post('update', 'TimeGroupController@update');
    $router->post('delete', 'TimeGroupController@delete');
    $router->post('getAllSchedules', 'TimeGroupController@getAllSchedules');
    $router->post('getOneSchedule', 'TimeGroupController@getOneSchedule');
    $router->post('saveSchedule', 'TimeGroupController@saveSchedule');
    $router->post('updateSchedule', 'TimeGroupController@updateSchedule');
    $router->post('getScheduleForDate', 'TimeGroupController@getScheduleForDate');
    $router->post('getScheduleForDates', 'TimeGroupController@getScheduleForDates');
    $router->post('getScheduleByPerson', 'TimeGroupController@getScheduleByPerson');
    $router->post('getOneScheduleWithDate', 'TimeGroupController@getOneScheduleWithDate');
});

$router->group(['prefix' => 'timeSheet'], function () use ($router) {
    $router->post('getAll', 'TimeSheetController@getAll');
    $router->post('getAllRaw', 'TimeSheetController@getAllRaw');
    $router->post('getAllByPerson', 'TimeSheetController@getAllByPerson');
    $router->post('getAllRawByPerson', 'TimeSheetController@getAllRawByPerson');
    $router->post('getDisplayDataClocking', 'TimeSheetController@getDisplayDataClocking');
    $router->post('getLatestClockingData', 'TimeSheetController@getLatestClockingData');
    $router->post('getOne', 'TimeSheetController@getOne');
    $router->post('getOneRaw', 'TimeSheetController@getOneRaw');
    $router->post('saveRaw', 'TimeSheetController@saveRaw');
    $router->post('importRaw', 'TimeSheetController@importRaw');
    $router->post('updateRaw', 'TimeSheetController@updateRaw');
    $router->post('deleteRaw', 'TimeSheetController@deleteRaw');
    $router->post('search', 'TimeSheetController@search');
    $router->post('advancedSearch', 'TimeSheetController@advancedSearch');
    $router->post('searchRaw', 'TimeSheetController@searchRaw');
    $router->post('advancedSearchRaw', 'TimeSheetController@advancedSearchRaw');
    $router->post('analyze', 'TimeSheetController@analyze');
    $router->post('getOneWorkSheet', 'TimeSheetController@getOneWorkSheet');
    $router->post('getAllWorkSheetByPerson', 'TimeSheetController@getAllWorkSheetByPerson');
    $router->post('getAllWorkSheetByPersonAndDate', 'TimeSheetController@getAllWorkSheetByPersonAndDate');
    $router->post('saveWorkSheet', 'TimeSheetController@saveWorkSheet');
    $router->post('updateWorkSheet', 'TimeSheetController@updateWorkSheet');
    $router->post('generateTemplate', 'TimeSheetController@generateTemplate');
    $router->post('downloadAllReport', 'TimeSheetController@downloadAllReport');
});

$router->group(['prefix' => 'permissionRequest'], function () use ($router) {
    $router->post('getAll', 'PermissionRequestController@getAll');
    $router->post('getAllByEmployeeId', 'PermissionRequestController@getAllByEmployeeId');
    $router->post('getOne', 'PermissionRequestController@getOne');
    $router->post('save', 'PermissionRequestController@save');
    $router->post('update', 'PermissionRequestController@update');
});

$router->group(['prefix' => 'leaveRequest'], function () use ($router) {
    $router->post('getAll', 'LeaveRequestController@getAll');
    $router->post('getAllByEmployeeId', 'LeaveRequestController@getAllByEmployeeId');
    $router->post('getOne', 'LeaveRequestController@getOne');
    $router->post('getMany', 'LeaveRequestController@getMany');
    $router->post('search', 'LeaveRequestController@search');
    $router->post('save', 'LeaveRequestController@save');
    $router->post('saveMobile', 'LeaveRequestController@saveMobile');
    $router->post('update', 'LeaveRequestController@update');
    $router->post('checkDayOff', 'LeaveRequestController@checkDayOff');
});

$router->group(['prefix' => 'overtimeRequest'], function () use ($router) {
    $router->post('getAll', 'OvertimeRequestController@getAll');
    $router->post('getAllByEmployeeId', 'OvertimeRequestController@getAllByEmployeeId');
    $router->post('getAllOrderedForMe', 'OvertimeRequestController@getAllOrderedForMe');
    $router->post('getAllOrderedByMe', 'OvertimeRequestController@getAllOrderedByMe');
    $router->post('getAccumulationOvertime', 'OvertimeRequestController@getAccumulationOvertime');
    $router->post('getOne', 'OvertimeRequestController@getOne');
    $router->post('save', 'OvertimeRequestController@save');
    $router->post('update', 'OvertimeRequestController@update');
});

$router->group(['prefix' => 'timeDefinition'], function () use ($router) {
    $router->post('getAll', 'TimeDefinitionController@getAll');
    $router->post('getOne', 'TimeDefinitionController@getOne');
    $router->post('getLovAttendance', 'TimeDefinitionController@getLovAttendance');
    $router->post('getLov', 'TimeDefinitionController@getLov');
    $router->post('save', 'TimeDefinitionController@save');
    $router->post('update', 'TimeDefinitionController@update');
    $router->post('delete', 'TimeDefinitionController@delete');
});

$router->group(['prefix' => 'scheduleException'], function () use ($router) {
    $router->post('getAll', 'ScheduleExceptionController@getAll');
    $router->post('getOne', 'ScheduleExceptionController@getOne');
    $router->post('save', 'ScheduleExceptionController@save');
    $router->post('saveSwitchSchedule', 'ScheduleExceptionController@saveSwitchSchedule');
    $router->post('update', 'ScheduleExceptionController@update');
    $router->post('delete', 'ScheduleExceptionController@delete');
    $router->post('generateTemplate', 'ScheduleExceptionController@generateTemplate');
    $router->post('importRaw', 'ScheduleExceptionController@importRaw');

});

$router->group(['prefix' => 'timeAttribute'], function () use ($router) {
    $router->post('getAll', 'TimeAttributeController@getAll');
    $router->post('getAllForEmployee', 'TimeAttributeController@getAllForEmployee');
    $router->post('getOne', 'TimeAttributeController@getOne');
    $router->post('getOneByEmployeeId', 'TimeAttributeController@getOneByEmployeeId');
    $router->post('getHistory', 'TimeAttributeController@getHistory');
    $router->post('saveChangeGroup', 'TimeAttributeController@saveChangeGroup');
    $router->post('save', 'TimeAttributeController@save');
    $router->post('update', 'TimeAttributeController@update');
});

$router->group(['prefix' => 'quotaGenerator'], function () use ($router) {
    $router->post('getQuotaByEmployee', 'QuotaGeneratorController@getQuotaByEmployee');
    $router->post('getRemainingAndMaxLeaveQuotas', 'QuotaGeneratorController@getRemainingAndMaxLeaveQuotas');
    $router->post('create', 'QuotaGeneratorController@createQuotaGeneratorForHireEmployee');
    $router->post('update', 'QuotaGeneratorController@update');
    $router->post('forceGenerateQuota', 'QuotaGeneratorController@forceGenerateQuota');
});

$router->group(['prefix' => 'employeeAnnualLeave'], function () use ($router) {
    $router->post('getAll', 'EmployeeAnnualLeaveController@getAll');
    $router->post('getLeaveEmployee', 'EmployeeAnnualLeaveController@getLeaveEmployee');
});

$router->group(['prefix' => 'employee'], function () use ($router) {
    $router->post('advancedSearch', 'EmployeeTimesheetController@advancedSearch');
    $router->post('downloadAllReport', 'EmployeeTimesheetController@downloadAllReport');
});

$router->group(['prefix' => 'requestRawTimesheet'], function () use ($router) {
    $router->post('getAll', 'RequestRawTimesheetController@getAll');
    $router->post('getHistory', 'RequestRawTimesheetController@getHistory');
    $router->post('search', 'RequestRawTimesheetController@search');
    $router->post('getOne', 'RequestRawTimesheetController@getOne');
    $router->post('save', 'RequestRawTimesheetController@save');
    $router->post('update', 'RequestRawTimesheetController@update');
    $router->post('updateValue', 'RequestRawTimesheetController@updateValue');
    $router->post('delete', 'RequestRawTimesheetController@delete');
});

$router->group(['prefix' => 'worksheetActivity'], function () use ($router) {
    $router->post('getAll', 'WorkSheetActivityController@getAll');
    $router->post('getOne', 'WorkSheetActivityController@getOne');
    $router->post('search', 'WorkSheetActivityController@search');
    $router->post('save', 'WorkSheetActivityController@save');
    $router->post('update', 'WorkSheetActivityController@update');
    $router->post('delete', 'WorkSheetActivityController@delete');
    $router->post('getLov', 'WorkSheetActivityController@getLov');
});
