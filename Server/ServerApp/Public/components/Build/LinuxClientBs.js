import {API_GET_GIT_VERSION} from "../../public/api.js";
import {API_BUILD_LINUX_CLIENT_BS} from "../../public/api.js";

export default {
    name: 'LinuxClient',
    template: `
    <div class="build build-linux-client-bs">
        
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
                <option selected>RHEL6-x86_64</option>
                <option>RHEL7-x86_64</option>
                <option>NEOKLIN7-x86_64</option>           
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
          </div>
        </form>
        <div>
            <button type="button" class="btn btn-primary" :disabled="!!buildButtonDisabled" v-on:click="buildServer">Build Linux Client zip now</button>
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
            version: '3.4.0',
            arch: 'RHEL6-x86_64',
            release: 'alpha',
            encrypt: 'NC',
            buildButtonDisabled: false,
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
        buildServer: function () {
            this.buildButtonDisabled = true;
            API_BUILD_LINUX_CLIENT_BS({
                sVersion: this.version,
                sArch:    this.arch,
                sRelease: this.release,
                bEncrypt: this.encrypt === 'EC' ? 1 : 0,
            })
                .then((response) => {
                    console.log("THEN_1", response);
                    this.message = response.data.data;
                    $('#exampleModalCenter').modal();
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