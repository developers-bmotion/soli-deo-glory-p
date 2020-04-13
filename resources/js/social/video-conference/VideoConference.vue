<template>
  <div>
    <div class="input-field">
      <label for="appID" class="active">Código de la llamada</label>
      <input type="text" placeholder="codigo de llamada" v-model="option.uid" />
    </div>
    <div class="input-field">
      <label for="channel" class="active">Channel</label>
      <input type="text" placeholder="channel" v-model="option.channel" />
    </div>
    <!-- <div class="input-field">
        <label for="token" class="active">Token</label>
        <input type="text" placeholder="token" name="token" />
    </div>-->
    <div class="row" style="margin: 0">
      <div class="col-md-5">
        <button
          class="btn btn-raised btn-primary waves-effect waves-light"
          @click="joinButton"
        >UNIRSE</button>
        <button
          class="btn btn-raised btn-primary waves-effect waves-light"
          @click="leaveButton"
        >SALIR</button>
        <button class="btn btn-raised btn-primary waves-effect waves-light" id="publish">PUBLISH</button>
        <button class="btn btn-raised btn-primary waves-effect waves-light" id="unpublish">UNPUBLISH</button>
      </div>
      <div class="col-md-7">
        <div class="video-grid" id="video_stream">
          <div id="local_stream" class="video-placeholder"></div>
          <div id="local_video_info" class="video-profile show"></div>
          <div id="video_autoplay_local" class="autoplay-fallback show"></div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import AgoraRTC from "agora-rtc-sdk";
export default {
  name: "VideoConference",
  components: {
    AgoraRTC
  },
  data() {
    return {
      rtc: {
        client: null,
        joined: false,
        published: false,
        localStream: null,
        remoteStreams: [],
        params: {}
      },
      option: {
        appID: "2a8b6e70b90b4b778db4c2f0d905417c",
        channel: "",
        uid: null,
        token: null
      },

      resolutions: [
        {
          name: "default",
          value: "default"
        },
        {
          name: "480p",
          value: "480p"
        },
        {
          name: "720p",
          value: "720p"
        },
        {
          name: "1080p",
          value: "1080p"
        }
      ]
    };
  },
  computed: {},
  watch: {},
  methods: {
    /*=============================================
     FUNCIÓN QUE PERMITE AGREGAR EL VIDEO
   =============================================*/
    addView(id, show) {
      if (!$("#" + id)[0]) {
        $("<div/>", {
          id: "remote_video_panel_" + id,
          class: "video-view"
        }).appendTo("#video_stream");

        $("<div/>", {
          id: "remote_video_" + id,
          class: "video-placeholder"
        }).appendTo("#remote_video_panel_" + id);

        $("<div/>", {
          id: "remote_video_info_" + id,
          class: "video-profile " + (show ? "" : "hide")
        }).appendTo("#remote_video_panel_" + id);

        $("<div/>", {
          id: "video_autoplay_" + id,
          class: "autoplay-fallback hide"
        }).appendTo("#remote_video_panel_" + id);
      }
    },
    /*=============================================
     FUNCIÓN QUE PERMITE QUITAR EL VIDEO
   =============================================*/
    removeView(id) {
      if ($("#remote_video_panel_" + id)[0]) {
        $("#remote_video_panel_" + id).remove();
      }
    },

    getDevices(next) {
      AgoraRTC.getDevices(function(items) {
        items
          .filter(function(item) {
            return ["audioinput", "videoinput"].indexOf(item.kind) !== -1;
          })
          .map(function(item) {
            return {
              name: item.label,
              value: item.deviceId,
              kind: item.kind
            };
          });
        var videos = [];
        var audios = [];
        for (var i = 0; i < items.length; i++) {
          var item = items[i];
          if ("videoinput" == item.kind) {
            var name = item.label;
            var value = item.deviceId;
            if (!name) {
              name = "camera-" + videos.length;
            }
            videos.push({
              name: name,
              value: value,
              kind: item.kind
            });
          }
          if ("audioinput" == item.kind) {
            var name = item.label;
            var value = item.deviceId;
            if (!name) {
              name = "microphone-" + audios.length;
            }
            audios.push({
              name: name,
              value: value,
              kind: item.kind
            });
          }
        }
        next({ videos: videos, audios: audios });
      });
    },

    /*=============================================
        EVENTOS PARA INICIAR UNA SALA
   =============================================*/
    handleEvents(rtc) {
      let resp = this;
      // Occurs when an error message is reported and requires error handling.
      this.rtc.client.on("error", err => {
        console.log(err);
      });
      // Occurs when the peer user leaves the channel; for example, the peer user calls Client.leave.
      this.rtc.client.on("peer-leave", function(evt) {
        var id = evt.uid;
        console.log("id", evt);
        let streams = rtc.remoteStreams.filter(e => id !== e.getId());
        let peerStream = rtc.remoteStreams.find(e => id === e.getId());
        if (peerStream && peerStream.isPlaying()) {
          peerStream.stop();
        }
        rtc.remoteStreams = streams;
        if (id !== resp.option.uid) {
          resp.removeView(id);
        }

        console.log("peer-leave", id);
      });
      // Occurs when the local stream is published.
      rtc.client.on("stream-published", function(evt) {
        console.log("stream-published");
      });
      // Occurs when the remote stream is added.
      rtc.client.on("stream-added", function(evt) {
        var remoteStream = evt.stream;
        var id = remoteStream.getId();

        if (id !== resp.option.uid) {
          rtc.client.subscribe(remoteStream, function(err) {
            console.log("stream subscribe failed", err);
          });
        }
        console.log("stream-added remote-uid: ", id);
      });
      // Occurs when a user subscribes to a remote stream.
      rtc.client.on("stream-subscribed", function(evt) {
        var remoteStream = evt.stream;
        /* remoteStream.style.position = "sticky"; */
        var id = remoteStream.getId();
        rtc.remoteStreams.push(remoteStream);
        /* alert("remote agregar", id); */
        resp.addView(id);
        remoteStream.play("remote_video_" + id);

        console.log("stream-subscribed remote-uid: ", id);
      });
      // Occurs when the remote stream is removed; for example, a peer user calls Client.unpublish.
      this.rtc.client.on("stream-removed", function(evt) {
        var remoteStream = evt.stream;
        var id = remoteStream.getId();

        if (remoteStream.isPlaying()) {
          remoteStream.stop();
        }
        rtc.remoteStreams = rtc.remoteStreams.filter(function(stream) {
          return stream.getId() !== id;
        });
        resp.removeView(id);
        console.log("stream-removed remote-uid: ", id);
      });
      this.rtc.client.on("onTokenPrivilegeWillExpire", function() {
        // After requesting a new token
        // this.rtc.client.renewToken(token);

        console.log("onTokenPrivilegeWillExpire");
      });
      this.rtc.client.on("onTokenPrivilegeDidExpire", function() {
        // After requesting a new token
        // client.renewToken(token);

        console.log("onTokenPrivilegeDidExpire");
      });
    },

    /*=============================================
        ENTRAR A UNA SALA
   =============================================*/
    join(rtc, option) {
      let resp = this;
      if (this.rtc.joined) {
        return;
      }

      this.rtc.client = AgoraRTC.createClient({
        mode: "rtc",
        codec: "h264"
      });

      rtc.params = option;

      // handle AgoraRTC client event
      resp.handleEvents(rtc);

      // init client
      this.rtc.client.init(
        "2a8b6e70b90b4b778db4c2f0d905417c",
        function() {
          console.log("init success");

          rtc.client.join(
            null,
            resp.option.channel,
            null,
            function(uid) {
              rtc.joined = true;

              resp.option.uid = uid;

              // create local stream
              rtc.localStream = AgoraRTC.createStream({
                streamID: resp.option.uid,
                audio: false,
                video: true,
                screen: false
                /* microphoneId: option.microphoneId,
                cameraId: option.cameraId */
              });

              // init local stream
              rtc.localStream.init(
                function() {
                  console.log("init local stream success");
                  // play stream with html element id "local_stream"
                  rtc.localStream.play("local_stream");
                  console.log(rtc.localStream.play + "");
                  // publish local stream
                  resp.publish(rtc);
                },
                function(err) {
                  console.error("init local stream failed ", err);
                }
              );
            },
            function(err) {
              console.error("client join failed", err);
            }
          );
        },
        err => {
          console.error(err);
        }
      );
    },

    /*=============================================
        SALIR DE LA SALA
   =============================================*/
    leave(rtc) {
      let resp = this;
      if (!this.rtc.client) {
        return;
      }
      if (!this.rtc.joined) {
        return;
      }
      /**
       * Leaves an AgoraRTC Channel
       * This method enables a user to leave a channel.
       **/
      this.rtc.client.leave(
        function() {
          // stop stream
          if (this.rtc.localStream.isPlaying()) {
            this.rtc.localStream.stop();
          }
          // close stream
          this.rtc.localStream.close();
          for (let i = 0; i < rtc.remoteStreams.length; i++) {
            var stream = rtc.remoteStreams.shift();
            var id = stream.getId();
            if (stream.isPlaying()) {
              stream.stop();
            }
            resp.removeView(id);
          }
          rtc.localStream = null;
          rtc.remoteStreams = [];
          this.rtc.client = null;
          console.log("client leaves channel success");
          this.rtc.published = false;
          this.rtc.joined = false;
        },
        function(err) {
          console.log("channel leave failed");

          console.error(err);
        }
      );
    },

    publish(rtc) {
      if (!rtc.client) {
        return;
      }
      if (rtc.published) {
        return;
      }
      var oldState = rtc.published;

      // publish localStream
      rtc.client.publish(rtc.localStream, function(err) {
        rtc.published = oldState;
        console.log("publish failed");

        console.error(err);
      });

      rtc.published = true;
    },

    unpublish(rtc) {
      if (!rtc.client) {
        return;
      }
      if (!rtc.published) {
        return;
      }
      var oldState = rtc.published;
      rtc.client.unpublish(rtc.localStream, function(err) {
        rtc.published = oldState;
        console.log("unpublish failed");

        console.error(err);
      });

      rtc.published = false;
    },

    joinButton() {
      console.log("join");

      this.join(this.rtc, this.params);
    },

    leaveButton() {
      alert("Leave");
    },

    function(e, n, i) {
      "function" == typeof n && ((i = n), (n = null)),
        o.default.debug("[".concat(t.streamId, "] play()."), e, n);
      var a = s.b.reportApiInvoke(t.sid, {
        name: "Stream.play",
        options: arguments,
        tag: "tracer",
        callback: i
      });
      if (
        (Object(W.checkValidString)(e, "elementID"),
        Object(W.isEmpty)(n) ||
          (Object(W.isEmpty)(n.fit) ||
            Object(W.checkValidEnum)(n.fit, "fit", ["cover", "contain"]),
          Object(W.isEmpty)(n.muted) ||
            Object(W.checkValidBoolean)(n.muted, "muted")),
        t.player)
      )
        o.default.warning(
          "[".concat(
            t.streamId,
            "] Stream.play(): Stream is already playing. Fallback to resume stream"
          )
        ),
          t
            .resume()
            .then(function() {
              a(null);
            })
            .catch(a);
      else {
        (t.elementID = e),
          (t.playOptions = n),
          !t.local || t.video || t.screen
            ? ((t.player = new y({
                id: t.getId(),
                stream: t,
                elementID: e,
                options: n
              })),
              t.local && ge.applyEffectInPlayer(t))
            : t.hasAudio() &&
              (t.player = new y({
                id: t.getId(),
                stream: t,
                elementID: e,
                options: n
              }));
        var r = { audio: null, video: null };
        t.on("player-status-change", function e(n) {
          if (((r[n.mediaType] = n), r.audio && r.video))
            if (
              (t.removeEventListener("player-status-change", e),
              r.video.isErrorState || r.audio.isErrorState)
            ) {
              var i = r.video.isErrorState ? r.video : r.audio;
              a({
                isErrorState: !0,
                status: i.status,
                reason: i.reason,
                video: r.video,
                audio: r.audio
              });
            } else
              "aborted" === r.video.status && "aborted" === r.audio.status
                ? a({
                    status: "aborted",
                    reason: "stop",
                    video: r.video,
                    audio: r.audio
                  })
                : a(null);
        }),
          t.audioOutput && t.player.setAudioOutput(t.audioOutput),
          void 0 !== t.audioLevel && t.player.setAudioVolume(t.audioLevel),
          t._flushAudioMixingMuteStatus(!1);
      }
    }
  }
};
</script>

<style>
#video {
  width: 100%;
  height: 100%;
  position: initial !important;
}
.video-view {
  transform: rotateY(180deg);
}
.video-placeholder {
  height: 50vh;
}
</style>
