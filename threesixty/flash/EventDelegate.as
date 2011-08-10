class EventDelegate {
	
	private function EventDelegate () {
		// empty
	}
	
	public static function create(scope, method:Function):Function {
		var params:Array = arguments.splice(2, arguments.length - 2);
		var proxyFunc:Function = function() {
			return method.apply(scope, arguments.concat(params));
		}
		return proxyFunc;
	}
	
}
