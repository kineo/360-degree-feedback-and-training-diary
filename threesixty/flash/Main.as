class Main {
	
	static var debug_txt:TextField;
	
	// c'tor arguments
	var timeline:MovieClip;
	var analysisid:Number;
	var activityid:Number;
	var scriptURL:String;
	var filterz:Array;
	
	var callback:Function;
	var scoreFetcher:ScoreFetcher;
	var filterIndex:Number;
	var scores:Array;
	
	function Main(timeline:MovieClip, analysisid:Number, activityid:Number, scriptURL:String, filterz:Array) {
		if (!debug_txt && timeline.debug_txt) {
			debug_txt = timeline.debug_txt;
		}
		
		this.timeline = timeline;
		this.analysisid = analysisid;
		this.activityid = activityid;
		this.scriptURL = scriptURL;
		this.filterz = filterz;
		
		debug("Main::c'tor");
		debug("  analysisid is " + this.analysisid);
		debug("  activityid is " + this.activityid);
		debug("  script URL is \"" + this.scriptURL + "\"");
		debug("  there are " + this.filterz.length + " filters");
		
		if (this.filterz.length) {
			fetchAllData();
		} else {
			setStatus("no filters selected");
		}
	}
	
	static function debug(s:String):Void {
		if (debug_txt) {
			debug_txt.text += s;
			debug_txt.text += '\n';
			debug_txt.scroll = debug_txt.maxscroll;
		}
	}
	
	function fetchAllData():Void {
		debug("Main::fetchAllData");
		
		if (!callback) {
			callback = EventDelegate.create(this, onScoresFetched);
		}
		if (!scoreFetcher) {
			scoreFetcher = new ScoreFetcher(callback);
		}
		if (!scores) {
			scores = [];
		}
		
		filterIndex = 0;
		setStatus("loading \"" + filterz[filterIndex][1] + "\" ...");
		scoreFetcher.fetchDataForFilter(filterz[filterIndex][0], analysisid, activityid, scriptURL);
	}
	
	function onScoresFetched(success:Boolean) {
		debug("Main::onScoresFetched: success? " + success);
		
		if (success) {
			scores[filterz[filterIndex][0]] = scoreFetcher.scores.slice();
		}
		
		// fetch the next one, if there are any more to fetch
		++filterIndex;
		if (filterIndex < filterz.length) {
			setStatus("loading \"" + filterz[filterIndex][1] + "\" ...");
			scoreFetcher.fetchDataForFilter(filterz[filterIndex][0], analysisid, activityid, scriptURL);
		} else {
			setStatus();
			renderAllScores();
		}
	}
	
	function renderAllScores():Void {
		debug("Main::renderAllScores: there are " + scores.length + " scores/skills to render");
		var scoresRenderer:ScoresRenderer = new ScoresRenderer(timeline, scores, filterz);
		scoresRenderer.renderAll();
		scoresRenderer.enableRollover();
	}
	
	function setStatus(s:String):Void {
		if (!s || s.length == 0) {
			timeline.canvas_mc.status_mc._visible = false;
		} else {
			timeline.canvas_mc.status_mc._visible = true;
			var tf:TextField = timeline.canvas_mc.status_mc.text_txt;
			tf.selectable = false;
			tf.multline = true;
			tf.autoSize = "left";
			tf.text = s;
			tf._y = -0.5 * tf.textHeight;
		}
	}
	
}
