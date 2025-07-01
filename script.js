let cnt1 = 1000;
let cnt2 = 100;
let cnt3 = 111;
function followersFn() {
	cnt1 += 1;
	document.getElementById('followersCount').innerText = cnt1;
}
function followingFn() {
	cnt2 += 1;
	document.getElementById('followingCount').innerText = cnt2;
}
function articlesFn() {
	cnt3 += 1;
	document.getElementById('articlesCount').innerText = cnt3;
}
