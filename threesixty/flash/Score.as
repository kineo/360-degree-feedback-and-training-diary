class Score {
	
	var skill:Skill;
	var score:Number; // integer
	
	function Score(skill:Skill, score:Number) {
		this.skill = skill;
		this.score = score;
	}
	
	function toString():String {
		var s:String = "[";
		s += "score=" + score;
		s += ",skill=";
		s += skill.toString();
		s += "]";
		return s;
	}
	
	static function sorter(a:Score, b:Score):Number {
		var ordinal1:Number = a.skill.ordinal;
		var ordinal2:Number = b.skill.ordinal;
		if (ordinal1 < ordinal2) {
			return -1;
		} else if (ordinal1 > ordinal2) {
			return 1;
		} else {
			return 0;
		}
	}
	
}
