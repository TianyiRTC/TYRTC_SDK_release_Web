package ctbri;

import java.io.BufferedReader;
import java.io.DataOutputStream;
import java.io.IOException;
import java.io.InputStreamReader;
import java.io.UnsupportedEncodingException;
import java.net.HttpURLConnection;
import java.net.MalformedURLException;
import java.net.URL;

import org.json.*;
import org.apache.commons.codec.binary.Base64;

public class Ctrtc{
	public static int ACC_INNER  = 10;
	public static int ACC_ESURF = 11;
	public static int ACC_WEIBO = 12;
	public static int ACC_QQ =13;

	public String appid;
	public String appkey;
	public String restServer = "rest.chinartc.com:8090";

	private Ctrtc(String id,String key){
		this.appid = id;
		this.appkey = key;
	}

	private static Ctrtc ctrtc=null;

	public synchronized static Ctrtc getInstance(String id,String key){
		if(ctrtc ==null){
			ctrtc = new Ctrtc(id,key);
		}
		return ctrtc;
	}
	

	public String generateToken(int pid,String username,String terminalType) throws JSONException{
		if(username==null){
			return "ERR_NO_USERNAME";//û���û���
		}
		else if(pid!=ACC_INNER && pid!=ACC_ESURF && pid!=ACC_WEIBO && pid!=ACC_QQ){
			return "ERR_PID_ILLEGAL";//����Ӧ�ô�Ŵ���
		}
		else if(terminalType==null){
			return "ERR_NO_TERMINALTYPE";//û���ն�����
		}
		else if((terminalType.equals("Browser")||terminalType.equals("TV")||terminalType.equals("Phone")||terminalType.equals("Pad"))==false){
			return "ERR_TERMINALTYPE_ILLEGAL";//�ն����ʹ���
		}
		String APIversion = "0.1";
		String authType_Token = "0";
		String appAccountID_Token = pid+"-"+username;
		String userTerminalType_Token = terminalType;
		long  userTerminalSN_Token = System.currentTimeMillis();
		
		String grantedCapabilityID_Token = "100<200<301<302<303<400";
		String callbackURL_Token = "www.baidu.com";
		String url_Token = "http://"+this.restServer+"/RTC/ws/"+APIversion+"/ApplicationID/"+this.appid+"/CapabilityToken";
		try{
		URL url = new URL(url_Token);
		HttpURLConnection connection = (HttpURLConnection) url.openConnection();
		connection.setDoOutput(true);
		connection.setDoInput(true);
		connection.setRequestMethod("POST");
        connection.setUseCaches(false);
        connection.setInstanceFollowRedirects(true);
        connection.setRequestProperty("Content-Type","application/json");
        connection.setRequestProperty("authorization","RTCAUTH,realm=AppServer,ApplicationId="+this.appid+",APP_Key="+this.appkey);
        connection.connect();
       
        DataOutputStream out = new DataOutputStream(connection.getOutputStream());
        JSONObject obj = new JSONObject();
        obj.put("authType", authType_Token);
        obj.put("appAccountID", appAccountID_Token);
        obj.put("userTerminalType", userTerminalType_Token);
        obj.put("userTerminalSN", userTerminalSN_Token);
        obj.put("grantedCapabiltyID", grantedCapabilityID_Token);
        obj.put("callbackURL", callbackURL_Token);

        out.writeBytes(obj.toString());
        out.flush();
        out.close();
        
        BufferedReader reader = new BufferedReader(new InputStreamReader(connection.getInputStream()));
        String lines;
        StringBuffer sb = new StringBuffer("");
        while ((lines = reader.readLine()) != null) {
            lines = new String(lines.getBytes(), "utf-8");
            sb.append(lines);
        }
        System.out.println(sb);
        String sbstring = sb.toString();
        if(sbstring.equals("1001"))
        {
        	return "FAIL_CONNECT_RTC_PLATFORM";
        }
        else if(sbstring.equals("1002"))
        {
        	return "FAIL_AUTHENTICATION";
        }
        else{
        JSONObject sbjson = new JSONObject(sbstring);
        String web_sip_password = sbjson.getString("capabilityToken");
        reader.close();
        connection.disconnect();
        String token = web_sip_password+"|"+username+"|"+pid+"|"+this.appid+"|"+userTerminalSN_Token+"|"+userTerminalType_Token;
        System.out.println("token:"+token);
        String tokenbase64 = null;
        tokenbase64 = this.encodeStr(token);
        System.out.println("tokenbase64:"+tokenbase64);	
        return tokenbase64;
        }
		}catch (MalformedURLException e) {
            // TODO Auto-generated catch block
            e.printStackTrace();
        } catch (UnsupportedEncodingException e) {
            // TODO Auto-generated catch block
            e.printStackTrace();
        } catch (IOException e) {
            // TODO Auto-generated catch block
            e.printStackTrace();
        }
		return "error";	
		}
	
		
	public static String encodeStr(String plainText){
		byte[] b=plainText.getBytes();
		Base64 base64=new Base64();
		b=base64.encode(b);
		String s=new String(b);
		return s;
	}

}
