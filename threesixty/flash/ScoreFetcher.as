class ScoreFetcher {
	
	var lv:LoadVars;
	var scores:Array;
	var callback:Function;
	var firstTime:Boolean = true;
	
	function ScoreFetcher(callback:Function) {
		this.callback = callback;
	}
	
	function fetchDataForFilter(filter:String, analysisid:Number, activityid:Number, scriptURL:String):Void {
		Main.debug("ScoreFetcher::fetchDataForFilter: filter code is \"" + filter + "\"");
		scores = [];
		lv = new LoadVars();
		lv.analysisid = analysisid;
		lv.activityid = activityid;
		lv.filter = filter;
		lv.onLoad = EventDelegate.create(this, onDataFetched);
		lv.sendAndLoad(scriptURL, lv, "POST");
	}
	
	function onDataFetched(success:Boolean):Void {
		Main.debug("ScoreFetcher::onDataFetched: data fetched from PHP successfully? " + success);
		if (success) {
			if (lv.result == "success") {
				ScoresRenderer.COLOURS_BY_FILTER[lv.filter] = lv.colour;
				populateScoresFromLoadVars();
				callback(true);
			} else {
				callback(false);
			}
		} else {
			callback(false);
		}
	}
	
	function populateScoresFromLoadVars():Void {
		Main.debug("ScoreFetcher::populateScoresFromLoadVars: for filter name \"" + lv.name + "\"");
		
		for (var thing:String in lv) {
			if (thing.substr(0, 6) == "skill_") {
				// strip string
				var bits:Array = thing.split('_');
				var competencyName:String = bits[1];
				var skillName:String = bits[2];
				var skillOrdinal:Number = parseInt(bits[3]);
				var skillScore:Number = parseInt(lv[thing]);
				
				// get the competency
				var competency:Competency = Competency.getCompetencyByName(competencyName);
				if (!competency) {
					competency = new Competency(skillOrdinal, competencyName);
				}
				
				// get the skill
				var skill:Skill = Skill.getSkillByName(skillName);
				if (!skill) {
					skill = new Skill(skillOrdinal, skillName, competency);
				}
				
				// increment the number of the skills in the current competency
				if (firstTime) {
					++competency.skillCount;
				}
				
				// add the score
				var score:Score = new Score(skill, skillScore);
				scores.push(score);
			}
		}
		
		// make sure the scores are displayed in the correct order
		scores.sort(Score.sorter);
		
		// sort the skills and competencies
		if (firstTime) {
			Competency.allCompetencies.sort(Competency.sorter);
			Skill.allSkills.sort(Skill.sorter);
			
			// and now it's not the first time any more
			firstTime = false;
		}
	}
	
}
