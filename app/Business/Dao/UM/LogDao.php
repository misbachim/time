<?php
namespace App\Business\Dao\UM;

use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use App\Business\Model\Requester;
use App\Business\Dao\Notification\UserNotificationDao;

class LogDao
{
	public function __construct(Requester $requester)
	{
		$this->requester = $requester;
	}



    /**
     * Insert impacted values into logs.
     *
     * @param bigInt $logId
     * @param string $table
     * @param array $arr
     * @return insert
     */
	public static function insertLog(Requester $requester, int $userId, string $service, $routePrefix, string $routeAction)
	{
		$api = DB::connection('log')->table('apis')->select('id')->where([
		    ['service', $service]
		  , ['route_prefix', $routePrefix]
		  , ['route_action', $routeAction]
		])->first();

		if($api) {
			$temp = [
			    'created_at'=>Carbon::now()
			  , 'user_id'=>$userId
			  , 'api_id'=>$api->id
			  , 'tenant_id'=>$requester->getTenantId()
			  , 'company_id'=>$requester->getCompanyId()
			];

			return DB::connection('log')->table('logs')->insertGetId($temp);
		}
		else {
			return null;
		}
	}



    /**
     * Insert into logs impacted values.
     *
     * @param bigInt $logId
     * @param string $table
     * @param array $arr
     * @return insert
     */
	public static function insertLogImpact($logId, string $table, array $arr)
	{
		//Get list of columns which must be ignored.
		$ignored_columns = 
			array_column(DB::connection('log')->table('ignored_columns')->select('column')->get()->toArray()
			           , 'column'
			);

		//Get data to insert.
		$data = [];
		foreach($arr as $key => $value) {

			//Get data only if value is not empty and key is not in the ignored columns.
			if( $value && (!in_array($key, $ignored_columns)) ) {

				$temp = [
				    'log_id'=>$logId
				  , 'table'=>$table
				  , 'column'=>$key
				  , 'value'=>$value
				];

				array_push($data,$temp);
			}

		}

		//Insert data.
		return DB::connection('log')->table('log_impacts')->insert($data);
	}



}
?>
