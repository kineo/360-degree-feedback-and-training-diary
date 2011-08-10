class Point {
	
	var x:Number;
	var y:Number;
	
	function Point(x:Number, y:Number) {
		this.x = x;
		this.y = y;
	}
	
	function clone():Point {
		return new Point(x, y);
	}
	
}
