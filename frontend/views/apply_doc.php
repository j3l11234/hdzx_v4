<?php
use yii\helpers\Html;

?>

<div style="font-size: 10pt; ">
  <div style="font-size: 20pt; font-weight: 400; text-align: center;" >学生活动服务中心场地申请审批表</div>
  <div style="font-size: 0pt; ">
    <div style="font-size: 12pt; font-weight: bold; text-align: center;">知情告知书</div>
    <div style="font-size: 10.5pt; line-height: 12pt; text-align: left; text-indent: 6em;">
    学生活动服务中心所有教室只能用于举办学校有利于校园文化建设的学生活动等，全部免费使用，不收任何场租费。各单位不得以任何名义挪作他用或转租给其他校外培训机构使用，不允许举办非学术性质的商业活动。如果发现有违反本规定的行为，管理人员有权中止活动进行，并将违反规定行为向全校通报，同时中止该单位今后申请教室的权利。让我们共同营造良好的校园学术文化氛围，保证正常的学生活动秩序。
    </div>
    <div style="font-size: 10.5pt; line-height: 20pt; text-align: left; text-indent: 6em;">
    我单位已阅读以上规定，保证本活动无任何培训机构商业宣传、报名行为。
    </div>
    <div style="font-size: 10.5pt; text-align: right;" >
      （负责人手写签名）
    </div>
  </div>

  <table border="1" align="center" cellpadding="3" style="font-size:10.5pt;">
    <tr>
      <td colspan="2">活动主题</td>
      <td colspan="8"><?= Html::encode($order['title']) ?></td>
    </tr>
    <tr>
      <td colspan="2">申请学号</td>
      <td colspan="3"><?= Html::encode($order['student_no']) ?></td>
      <td colspan="2">申请场地</td>
      <td colspan="3"><?= Html::encode($order['room_name']) ?></td>
    </tr>
    <tr>
      <td colspan="2">活动人数</td>
      <td colspan="3"><?= Html::encode($order['number']) ?></td>
      <td colspan="2">多媒体灯光音响</td>
      <td colspan="3"><?= $order['need_media'] == 1 ? '需要' : '不需要' ?></td>
    </tr>
    <tr>
      <td colspan="2">活动类型</td>
      <td colspan="3"><?= Html::encode($order['activity_type']) ?></td>
      <td colspan="2">举办单位</td>
      <td colspan="3"><?= Html::encode($order['dept_name']) ?></td>
    </tr>
    <tr>
      <td colspan="2">活动日期</td>
      <td colspan="3"><?= Html::encode($order['date']) ?></td>
      <td colspan="2">时间段</td>
      <td colspan="3"><?= Html::encode($order['start_hour'].'时 - '.$order['end_hour'].'时') ?></td>
    </tr>
    <tr>
      <td colspan="2">负责同学</td>
      <td colspan="3"><?= Html::encode($order['prin_student']) ?></td>
      <td colspan="2">联系电话</td>
      <td colspan="3"><?= Html::encode($order['prin_student_phone']) ?></td>
    </tr>
    <tr>
      <td colspan="2">负责老师</td>
      <td colspan="3"><?= Html::encode($order['prin_teacher']) ?></td>
      <td colspan="2">联系电话</td>
      <td colspan="3"><?= Html::encode($order['prin_teacher_phone']) ?></td>
    </tr>
    <tr>
      <td colspan="2" style="line-height: 40pt;">活动内容</td>
      <td colspan="8" style="text-align: left; font-size: 10pt; text-indent:6em;">
        <?= Html::encode($order['content']) ?>
      </td>
    </tr>
    <tr>
      <td colspan="2" style="line-height: 40pt;">安全措施</td>
      <td colspan="8" style="text-align: left; font-size: 10pt; text-indent:6em;">
        <?= Html::encode($order['secure']) ?>
      </td>
    </tr>
    <tr>
      <td colspan="2" style="line-height: 40pt;">主管部门意见</td>
      <td colspan="8" style="font-size: 1pt;">
        <div style="font-size: 20pt; text-align: center;">同意</div>
        <div style="font-size: 10.5pt; text-align: right"><?= date('Y年m月d日', $order['apply_time']) ?></div>
      </td>
    </tr>
    <tr>
      <td colspan="2" style="line-height: 40pt;">校团委意见</td>
      <td colspan="8" style="font-size: 1pt;">
        <div style="font-size: 20pt; text-align: center;">同意</div>
        <div style="font-size: 10.5pt; text-align: right"><?= date('Y年m月d日', $order['apply_time']) ?></div>
      </td>
    </tr>
    <tr>
      <td colspan="2" style="line-height: 40pt;">会议中心意见</td>
      <td colspan="8" style="font-size: 5pt;">
        <br />
        <br />
        <span style="font-size: 10.5pt;">(签字盖章)</span>    
        <br />
        <div style="font-size: 10.5pt; text-align: right">____年__月__日</div>
      </td>
    </tr>
  </table>
  <ol style="font-size: 10.5pt;">
    <li><b>本表须上交至会议中心审核(机械工程楼北门一楼右手边会议室)，后一份交至学活4楼控制室、一份交至学活一楼保卫室。以上流程至少需在活动开展两天前完成。</b></li>
    <li>使用多媒体教室、多功能厅或小剧场必须有老师到场(即现场负责老师)。</li>
    <li>原则上活动开始前十分钟开启多媒体设备，有特殊要求请与负责多媒体教师协商。</li>
    <li>活动期间严禁开关投影机，活动后离场即可，待多媒体教师关闭投影。</li>
    <li>动用灯光，桌椅须经教师同意，活动结束后尽快离场。</li>
  </ol>

</div>