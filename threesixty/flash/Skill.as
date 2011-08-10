class Skill {
	
	static var allSkills:Array;
	
	var ordinal:Number;
	var name:String;
	var competency:Competency;
	
	function Skill(ordinal:Number, name:String, competency:Competency) {
		this.ordinal = ordinal;
		this.name = name;
		this.competency = competency;
		if (!allSkills) {
			allSkills = [];
		}
		allSkills.push(this);
	}
	
	function toString():String {
		var s:String = "[";
		s += "ordinal=" + ordinal;
		s += ",name=" + name;
		s += ",comp=" + competency.toString();
		s += "]";
		return s;
	}
	
	static function debugAllSkills():Void {
		Main.debug("Skill::debugAllSkills:");
		for (var i:Number = 0; i < allSkills.length; ++i) {
			Main.debug(allSkills[i].toString());
		}
	}
	
	static function getSkillByName(name:String):Skill {
		if (!allSkills) {
			return null;
		}
		var retval:Skill = null;
		for (var i:Number = 0; i < allSkills.length; ++i) {
			var skill:Skill = allSkills[i];
			if (skill.name == name) {
				retval = skill;
				break;
			}
		}
		return retval;
	}
	
	static function sorter(a:Skill, b:Skill):Number {
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
