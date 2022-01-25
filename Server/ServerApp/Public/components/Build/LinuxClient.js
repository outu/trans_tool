import {API_CHECK_BUILDER_ENV, API_GET_GIT_VERSION} from "../../public/api.js";
import {API_BUILD_LINUX_CLIENT} from "../../public/api.js";

export default {
    name: 'LinuxClient',
    template: `
    <div class="build build-linux-client">
        
        <div class="card mb-4">
            <div class="card-body">
                <div> Git version: {{ gitVersion }}</div>
            </div>
        </div>
        <form>
        <div class="form-row">
            <div class="form-group col-md-4">
              <label for="inputVersion">Version</label>
              <input type="text" class="form-control" id="inputVersion" placeholder="2.4.0" v-model="version">
            </div>
            <div class="form-group col-md-4">
              <label for="inputVersion">Arch</label>
              <select id="inputRelease" class="form-control" v-model="arch">
                <option selected>RHEL_V6_INTEL-x86_64</option>
                <option>NFS_SERVER_V3.1_INTEL-x86_64</option>
                <option>NFS_SERVER_V4.0_INTEL-amd64</option>
                <option>NFS_DESKTOP_V3.1_ZXHG-amd64</option>
                <option>KYLIN_DESKTOP_V10_INTEL-amd64</option>           
                <option>KYLIN_DESKTOP_V10_LOONGSON-mips64el</option>
                <option>KYLIN_DESKTOP_V10_PHYTIUM-arm64</option>
                <option>KYLIN_SERVER_V10_INTEL-x86_64</option>
                <option>KYLIN_SERVER_V10_LOONGSON-mips64el</option>
                <option>KYLIN_SERVER_V10_PHYTIUM-aarch64</option>
              </select>
            </div>
            <div class="form-group col-md-2">
              <label for="inputRelease">Encrypt</label>
              <select id="inputRelease" class="form-control" v-model="encrypt">
                <option selected>NC</option>
                <option>EC</option>
              </select>
            </div>
            <div class="form-group col-md-2">
              <label for="inputRelease">Release</label>
              <select id="inputRelease" class="form-control" v-model="release">
                <option selected>alpha</option>
                <option>beta</option>
                <option>release</option>
                <option>rc</option>
                <option>stable</option>
              </select>
            </div>
            <div class="form-group col-md-4">
              <label for="inputType">Package Type</label>
              <select id="inputType" class="form-control" v-model="type">
                <option selected value="rpm">rpm</option>
                <option value="deb">deb</option>
                <option value="zip">zip</option>
              </select>
            </div>
             <div class="form-group col-md-4">
              <label for="inputIp">Os Packer Ip</label>
              <input type="text" :disabled="type == 'zip'" class="form-control" id="inputIp" placeholder="127.0.0.1" v-model="ip">
            </div>
             <div class="form-group col-md-2">
              <label for="inputPassword">Os Packer Password</label>
              <input type="password" :disabled="type == 'zip'" class="form-control" id="inputPassword" placeholder="" v-model="password">
            </div>
             <div class="form-group col-md-2">
              <button type="button" class="btn btn-primary" style="margin-top: 30px;" :disabled="!!buildButtonDisabled" v-on:click="EnvTesting">Environmental detection</button>
            </div>
          </div>
        </form>
        <div>
            <button type="button" class="btn btn-primary" :disabled="!!buildButtonDisabled || typeFlag" v-on:click="buildServer">Build Linux Client now</button>
        </div>
        <div v-if="!!buildButtonDisabled" class="spinner-border text-primary mt-2" role="status"></div>
        <!-- Modal -->
        <div class="modal fade" id="exampleModalCenter" tabindex="-1" role="dialog" aria-labelledby="exampleModalCenterTitle" aria-hidden="true">
          <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
              <div class="modal-header">
                <h5 class="modal-title" id="exampleModalCenterTitle">Modal title</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                  <span aria-hidden="true">&times;</span>
                </button>
              </div>
              <div class="modal-body">
                {{ message }}
              </div>
              <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
              </div>
            </div>
          </div>
        </div>
    </div>
    `,
    data(){
        return {
            message: '',
            gitVersion: '',
            version: '3.7.1',
            arch: 'RHEL_V6_INTEL-x86_64',
            release: 'alpha',
            encrypt: 'NC',
            type: 'rpm',
            ip: '127.0.0.1',
            password: '',
            buildButtonDisabled: false,
            typeFlag: true,
        }
    },
    mounted(){
        API_GET_GIT_VERSION()
            .then((response) => {
                console.log(response);
                this.gitVersion = response.data.data;
            })
            .catch((error) => {
                console.log(error);
            });
    },
    methods: {
        EnvTesting(){
            this.checkServerEnv();
        },
        buildServer: function () {
            this.buildButtonDisabled = true;
            API_BUILD_LINUX_CLIENT({
                sVersion: this.version,
                sArch:    this.arch,
                sRelease: this.release,
                bEncrypt: this.encrypt === 'EC' ? 1 : 0,
                sType: this.type,
                sIp: this.ip,
                sPasswd: this.password,
            })
                .then((response) => {
                    console.log("THEN_1", response);
                    this.message = response.data.data;
                    $('#exampleModalCenter').modal();
                    this.buildButtonDisabled = false;
                    this.typeFlag = false;
                })
                .catch((error) => {
                    console.log("CATCH_1", error);
                    this.message = error;
                    $('#exampleModalCenter').modal();
                    this.buildButtonDisabled = false;
                    this.typeFlag = false;
                });
        },
        checkServerEnv(){
            this.buildButtonDisabled = true;
            API_CHECK_BUILDER_ENV({
                sArch:    this.arch,
                sType: this.type,
                sIp: this.ip,
                sPasswd: this.password,
            })
                .then((response) => {
                    console.log("THEN_1", response);
                    this.message = response.data.data;
                    $('#exampleModalCenter').modal();
                    this.typeFlag = false;
                    this.buildButtonDisabled = false;
                })
                .catch((error) => {
                    console.log("CATCH_1", error);
                    this.message = error;
                    $('#exampleModalCenter').modal();
                    this.buildButtonDisabled = false;
                });
        }
    }

};