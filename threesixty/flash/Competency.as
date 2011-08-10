class Competency {
	
	static var allCompetencies:Array;
	
	var ordinal:Number;
	var name:String;
	var skillCount:Number;
	
	function Competency(ordinal:Number, name:String) {
		this.ordinal = ordinal;
		this.name = name;
		this.skillCount = 0;
		if (!allCompetencies) {
			allCompetencies = [];
		}
		allCompetencies.push(this);
	}
	
	static function getCompetencyByName(name:String):Competency {
		if (!allCompetencies) {
			return null;
		}
		var retval:Competency = null;
		for (var i:Number = 0; i < allCompetencies.length; ++i) {
			var competency:Competency = allCompetencies[i];
			if (competency.name == name) {
				retval = competency;
				break;
			}
		}
		return retval;
	}
	
	static function debugAllCompetencies():Void {
		Main.debug("Competency::debugAllCompetencies:");
		for (var i:Number = 0; i < allCompetencies.length; ++i) {
			Main.debug(allCompetencies[i].toString());
		}
	}
	
	function toString():String {
		var s:String = "[";
		s += "name=" + name;
		s += ",ordinal=" + ordinal.toString() + ",skill_count=";
		s += skillCount.toString();
		s += "]";
		return s;
	}
	
	static function sorter(a:Competency, b:Competency):Number {
		var ordinal1:Number = a.ordinal;
		var ordinal2:Number = b.ordinal;
		if (ordinal1 < ordinal2) {
			return -1;
		} else if (ordinal1 > ordinal2) {
			return 1;
		} else {
			return 0;
		}
	}
	
}
