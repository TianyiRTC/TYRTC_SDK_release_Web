<?php
include "./ctrtc.php";
$appaccount = $_GET["user"];
$terminalType = $_GET["terminal"];

if(empty($appaccount))$appaccount = time();
if(empty($terminalType))$terminalType = "Browser";

$appid = "changeit";
$appkey = "changeit";

//$appid = "70038";
//$appkey = "MTQxMDkyMzU1NTI4Ng==";

$ctrtc = Ctrtc::getInstance($appid,$appkey);
$token = $ctrtc->generateToken(Ctrtc::ACC_INNER,$appaccount,$terminalType);

?>

<!DOCTYPE html>
<html>
	<head>
		<title>CTRTC JSSDK DEMO</title>
		<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
		<script src="ctrtc.min.js" type="text/javascript"></script><!-- 引用jssdk rtc.js -->
		<script src="jquery-1.6.1.min.js" type="text/javascript"></script>
		<style type="text/css">
				.panelLocal{border:1px solid #F00;width:220px }
				.panelRemote{border:1px solid #336666;width:640px}
		</style>
	</head>
	<body>
		应用ID[<?php echo $appid; ?>]用户[<?php echo $appaccount; ?>]终端类型[<?php echo $terminalType; ?>]</><br>
		<button onclick="call();">
			呼叫
		</button>
    <button onclick="hangup();">
      全部挂断
    </button>
		<input type="text" id="callee" placeholder="请输入远端用户名"/>
		<!--<select id="terminalType">
			<option value="Any">任意终端类型</option>
			<option value="Browser">浏览器</option>
			<option value="TV">电视</option>
			<option value="Phone">手机</option>
			<option value="PC">电脑</option>		
		</select>-->
		<select id="mediaType">
			<option value="video">视频</option>
			<option value="audio">语音</option>		
		</select>
		分辨率
		<select id="videoResolution">
			<option value="default">默认</option>
			<option value="cif">CIF</option>
			<option value="qvga">QVGA</option>
			<option value="vga">VGA</option>
			<option value="hd">HD</option>
		</select>
		<!--<select id="audioSelect">
		</select>
		<select id="videoSelect">
		</select>-->
		

		<div id="divlog" style="display:inline" >连接中..</div>
		<div id="div_local_view" class=panelLocal>
				本地画面<br></><video id="selfView" autoplay controls Width=200  Height=150></video>
		</div>
		<div id="div_remote_views">
			  远程画面组
		</div>
		<script type="text/javascript">
			
			//初始化Ctrtc.Device这个全局单例
			//参数1为必选为平台SDK获得的令牌
			//参数2为可选，dbg:true会在浏览器的console台中打印用于调试的消息，在生产环境建议关闭
			//参数3为可选，defaultResolution指定默认视频分辨率（主叫和被叫时均有效）(不指定则使用摄像头设备的默认分辨率)，
			//参数3在被设置后仍然可以在通话前改变分辨率，参数3取值范围有qvga/vga/hd/cif四种
			Ctrtc.Device.init("<?php echo $token; ?>",{dbg:true,defaultResolution:'cif'});
			
			//Device初始化成功后会回调该函数
			Ctrtc.Device.onReady(function (e) {
				$("#divlog").text("准备就绪");
			});
			
			//注册当有SDK本身错误发生时JSSDK会回调的处理函数，如令牌无效、网络断开等事件
			Ctrtc.Device.onDevErr(function (e) {
				$("#divlog").text("出错，原因:" + e.info);
			});
			
			//注册当有新呼入或者新呼出事件时JSSDK会回调的处理函数（必选）
			//SDK会为每个收到的呼叫请求创建代表该请求的connection对象，应用通过该对象提供的accept/terminate方法可以控制接听和拒绝业务；
			//SDK也会为每个呼出请求创建一个代表该请求的connection对象，应用通过对象提供的cancel方法可以取消一个呼出的请求
			Ctrtc.Device.onConnNew(function (conn) {	
					//一般来说应用可以为每个connection创建一个独立的包含音视频播放和呼叫过程控制按钮的通话面板
					//SDK本身支持在一个页面中同时与多个用户进行通话，所以可能会同时存在多个通话面板
					createCallPanel(conn);		
			});
			
			//注册当被叫振铃时JSSDK会回调的处理函数
			Ctrtc.Device.onRinging(function (conn) {
				$("#" + conn.remoteAccount.username + " .log").text("对方" + conn.remoteAccount.username + "振铃中");
			});
			
			//注册当远端媒体流已经到达本地时JSSDK会回调的处理函数（必选，否则媒体流无法播放）
			//无论是作为主叫还是被叫，当两端媒体流成功建立起来时该函数都会被JSSDK回调
			//开发者在本函数中可自主指定渲染媒体流的video元素
			Ctrtc.Device.onStarted(function (conn) {
				//通过对方帐号的用户名找到呼叫控制面板，对面板下的子元素进行操作
				$("#" + conn.remoteAccount.username + " .log").text("与" + conn.remoteAccount.username + "的通话已开始");
				//使远端图像面板可见
				$("#" + conn.remoteAccount.username + " .divRemoteView").show();
				//使html5 video元素播放远端媒体流
				$("#" + conn.remoteAccount.username + " .videoView").attr('src',window.URL.createObjectURL(conn.remoteStream));
				
				//播放本地媒体流
				$("#selfView").attr('src',window.URL.createObjectURL(conn.localStream));
				
			});
			
			//注册当正常通话结束时JSSDK会回调的处理函数
			Ctrtc.Device.onEnded(function (conn) {
				$("#divlog").text("与" + conn.remoteAccount.username +  "的通话已结束:" + conn.info);
				//通过对方帐号的用户名找到呼叫控制面板，删除该面板
				$("#" + conn.remoteAccount.username).remove();
			});
			
			//注册当呼叫失败时JSSDK会回调的处理函数
			Ctrtc.Device.onConnFailed(function (conn) {
				$("#divlog").text("与" + conn.remoteAccount.username +"通话失败，原因:" + conn.info);
				//通过对方帐号的用户名找到呼叫控制面板，删除该面板
				$("#" + conn.remoteAccount.username).remove();
			});
			
			//注册视频媒体流统计结果处理函数，回调时stats对象中包含rtt(往返总时延)、媒体路径、丢包数量等，该接口在一次通话中会周期回调
			Ctrtc.Device.onStats(function (conn,stats) {
				var panelID = conn.remoteAccount.username;
				$("#" + panelID + " .stats").text("媒体路径 本地:" + stats.connectionType.local.candidateType + " " + stats.connectionType.local.ipAddress + " 对端:" + stats.connectionType.remote.candidateType + " " + stats.connectionType.remote.ipAddress + "视频统计:rtt" + stats.video.rtt + "丢包" + stats.video.packetsLost + "发送" + stats.video.packetsSent + "nack" + stats.video.googNacksReceived );
			});
			
			//只有当调用Device.run函数，JSSDK才会向能力平台建立网络连接注册本客户端并进入事件循环
			//需要保证run函数的执行顺序一定在上面注册业务回调处理函数完成后才执行
			Ctrtc.Device.run();
			
			
			function call() {
				//Device.connect函数用于发出呼叫
				//执行本函数后，onConnNew函数会立刻被JSSDK回调，SDK把为这个呼叫新创建的connnection对象返回给应用，应用通过该对象能控制呼叫后续过程
				//connect函数参数说明：
				//第一参数（必填）指定被叫用户名;
				//第二参数（可选）
					//mediaType 指定媒体类型：video(只视频)/audio(只音频)/both(音视频),不填写则SDK默认为both;
					//accType指定帐号体系：inner(开发者内部帐号)/esurf（天翼帐号）/weibo(新浪微博)/qq(QQ帐号),不填写则与主叫的帐号体系相同
					//videoResolution指定分辨率：有qvga/vga/hd/cif，不填写时如果在device初始化时指定了默认分辨率则使用这个分辨率，否则SDK选择摄像头支持的默认分辨率
				Ctrtc.Device.connect($('#callee').attr("value"),{mediaType:$('#mediaType').attr("value"),extraInfo:'chm',videoResolution:$('#videoResolution').attr("value")});
			};
			
			function hangup() {
				//挂断所有会话
				Ctrtc.Device.disconnectAll();
			};
			
			//创建通话面板，一般来说应用可以为每个新呼叫(呼入或者呼出)创建一个独立的面板，里面包括音视频播放的html5元素和接听、挂断的按钮等
			function createCallPanel(conn)
			{
				//由于可能在页面中同时存在多个进行中的通话，所以需要给每个通话面板一个ID，我们可以用对方账户的用户名作为唯一标识赋值给该通话面板的ID属性
				//以便于后续在SDK回调的事件通知接口中,通过发生该事件的connection对象的对方账户用户名找到所关联的通话面板，随之改变通话面板的状态
				var panelID = conn.remoteAccount.username;
				var panelHtml = '<div id=XXXX class="panelRemote"><div class="divRemoteView" style="display: none"><video class="videoView" autoplay controls Width=600  Height=450></video></div><div class="log"></div><div class="stats"></div><button class="accept_audio">语音接听</button><button class="accept_video">视频接听</button><button class = "hangup">挂断</button></div>';
				panelHtml = panelHtml.replace("XXXX",panelID);
				var callPanel = $(panelHtml);
				$("#div_remote_views").append(callPanel);
				
				$("#" + panelID + " .hangup").bind("click",function(){conn.terminate()});//对挂断按钮增加点击后挂断逻辑
			
				if(conn.direction == 'incoming')
				{//呼入请求
					$("#" + panelID + " .accept_audio").bind("click",function(){conn.accept({mediaType:"audio"});this.disabled = true; });//对使用语音接听按钮增加逻辑
					$("#" + panelID + " .accept_video").bind("click",function(){conn.accept({mediaType:"video",videoResolution:$('#videoResolution').attr("value")});this.disabled = true; });//对使用视频接听按钮增加逻辑
					$("#" + panelID + " .log").text("有来自" + conn.remoteAccount.username + "的"+ conn.remoteMedia +"呼叫");
				}else{//呼出请求
					$("#" + panelID + " .accept_audio").remove();//删除无用的语音接听按钮
					$("#" + panelID + " .accept_video").remove();//删除无用的视频接听按钮
					$("#" + panelID + " .log").text("呼叫" + conn.remoteAccount.username + "中...");
				}
				$("#divlog").text("");
				
			}
	
		</script>

	</body>
</html>
