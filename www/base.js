
// --------------------------------------------------
function showPw()
{
	var cbx = document.getElementById("showPass");
	var inp = document.getElementById("passwd");
	var inp2 = document.getElementById("passwd2");
	if( !cbx || !inp )
		return;
	if( cbx.checked )
	{
		inp.type = "text";
		if( inp2 )
			inp2.type = "text";
	}
	else
	{
		inp.type = "password";
		if( inp2 )
			inp2.type = "password";
	}
}
